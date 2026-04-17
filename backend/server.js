require('dotenv').config();
const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 5000;

// Configuration
const JWT_SECRET = process.env.JWT_SECRET || 'your-super-secret-jwt-key-change-this';

// Middleware
app.use(cors({
    origin: ['http://localhost:3000', 'http://localhost:19006', 'http://10.0.2.2:5000', 'http://localhost:8081'],
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization']
}));
app.use(express.json());

// Database connection
const db = mysql.createConnection({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || 'Narutostorm4',
    database: process.env.DB_NAME || 'gymnastics_academy'
});

db.connect((err) => {
    if (err) {
        console.error('Database connection failed:', err);
        return;
    }
    console.log('Connected to MySQL database');
});

// JWT Authentication Middleware
const authenticateToken = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (!token) {
        return res.status(401).json({ error: 'Access token required' });
    }
    
    jwt.verify(token, JWT_SECRET, (err, user) => {
        if (err) return res.status(403).json({ error: 'Invalid token' });
        req.user = user;
        next();
    });
};

// ============ LOGIN ENDPOINT (FIXED) ============
app.post('/api/login', (req, res) => {
    const { username, password } = req.body;
    
    console.log('Login attempt for username:', username);
    
    if (!username || !password) {
        return res.status(400).json({ error: 'Username and password required' });
    }
    
    const sql = 'SELECT * FROM users WHERE username = ?';
    db.query(sql, [username], async (err, results) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ error: 'Database error' });
        }
        
        console.log('User found:', results.length > 0);
        
        if (results.length === 0) {
            return res.status(401).json({ error: 'Invalid username or password' });
        }
        
        const user = results[0];
        
        try {
            // Compare password with bcrypt
            const validPassword = await bcrypt.compare(password, user.password);
            console.log('Password valid:', validPassword);
            
            if (!validPassword) {
                return res.status(401).json({ error: 'Invalid username or password' });
            }
            
            // Generate token
            const token = jwt.sign(
                { id: user.id, username: user.username, role: user.role },
                JWT_SECRET,
                { expiresIn: '24h' }
            );
            
            // Remove password from response
            delete user.password;
            
            res.json({ 
                success: true, 
                token, 
                user: { 
                    id: user.id, 
                    username: user.username, 
                    role: user.role,
                    email: user.email,
                    full_name: user.full_name
                } 
            });
        } catch (error) {
            console.error('Password comparison error:', error);
            res.status(500).json({ error: 'Login failed' });
        }
    });
});

// ============ GYMNAST ROUTES ============

// Get all gymnasts
app.get('/api/gymnasts', authenticateToken, (req, res) => {
    const sql = 'SELECT * FROM gymnasts ORDER BY created_at DESC';
    db.query(sql, (err, results) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        res.json(results);
    });
});

// Get gymnast by ID
app.get('/api/gymnasts/:id', authenticateToken, (req, res) => {
    const sql = 'SELECT * FROM gymnasts WHERE id = ?';
    db.query(sql, [req.params.id], (err, result) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        if (result.length === 0) {
            return res.status(404).json({ error: 'Gymnast not found' });
        }
        res.json(result[0]);
    });
});

// Register new gymnast
app.post('/api/gymnasts', authenticateToken, (req, res) => {
    const { membership_id, full_name, email, contact_no, date_of_birth, training_program, enrollment_date } = req.body;
    
    // Validate input
    if (!full_name || !email || !contact_no || !date_of_birth || !training_program || !enrollment_date) {
        return res.status(400).json({ error: 'Missing required fields' });
    }
    
    const sql = 'INSERT INTO gymnasts (membership_id, full_name, email, contact_no, date_of_birth, training_program, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?)';
    const generatedMembershipId = membership_id || `GYM${Math.floor(Math.random() * 9999)}`;
    
    db.query(sql, [generatedMembershipId, full_name, email, contact_no, date_of_birth, training_program, enrollment_date], (err, result) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        res.status(201).json({ 
            success: true, 
            id: result.insertId,
            message: 'Gymnast registered successfully' 
        });
    });
});

// Update gymnast with logging
app.put('/api/gymnasts/:id', authenticateToken, (req, res) => {
    const { full_name, email, contact_no, date_of_birth, training_program, enrollment_date, progress_status } = req.body;
    
    // Set the user for triggers (for logging)
    db.query('SET @updated_by = ?', [req.user.username]);
    
    const sql = 'UPDATE gymnasts SET full_name = ?, email = ?, contact_no = ?, date_of_birth = ?, training_program = ?, enrollment_date = ?, progress_status = ? WHERE id = ?';
    
    db.query(sql, [full_name, email, contact_no, date_of_birth, training_program, enrollment_date, progress_status, req.params.id], (err, result) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        if (result.affectedRows === 0) {
            return res.status(404).json({ error: 'Gymnast not found' });
        }
        res.json({ success: true, message: 'Gymnast updated successfully' });
    });
});

// Delete gymnast with logging
app.delete('/api/gymnasts/:id', authenticateToken, (req, res) => {
    // Set the user for triggers
    db.query('SET @deleted_by = ?', [req.user.username]);
    
    const deleteSql = 'DELETE FROM gymnasts WHERE id = ?';
    
    db.query(deleteSql, [req.params.id], (err, result) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        if (result.affectedRows === 0) {
            return res.status(404).json({ error: 'Gymnast not found' });
        }
        
        // Log deletion to file
        const logEntry = `[${new Date().toISOString()}] Deleted gymnast ID: ${req.params.id} by ${req.user.username}\n`;
        fs.appendFileSync('deletion_log.txt', logEntry);
        
        res.json({ success: true, message: 'Gymnast deleted successfully' });
    });
});

// Search gymnasts
app.get('/api/gymnasts/search/:keyword', authenticateToken, (req, res) => {
    const sql = 'SELECT * FROM gymnasts WHERE full_name LIKE ? OR membership_id LIKE ? OR email LIKE ?';
    const searchTerm = `%${req.params.keyword}%`;
    
    db.query(sql, [searchTerm, searchTerm, searchTerm], (err, results) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        res.json(results);
    });
});

// ============ ADDITIONAL ENDPOINTS FOR YOUR DATABASE SCHEMA ============

// Get user profile with gymnast details
app.get('/api/profile', authenticateToken, (req, res) => {
    const sql = `
        SELECT u.id, u.username, u.role, u.email, u.full_name, u.contact_no, 
               g.membership_id, g.training_program, g.progress_status, g.date_of_birth
        FROM users u
        LEFT JOIN gymnasts g ON u.gymnast_id = g.id
        WHERE u.id = ?
    `;
    db.query(sql, [req.user.id], (err, result) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        if (result.length === 0) {
            return res.status(404).json({ error: 'User not found' });
        }
        res.json(result[0]);
    });
});

// Get update logs for a gymnast
app.get('/api/gymnasts/:id/logs', authenticateToken, (req, res) => {
    const sql = 'SELECT * FROM update_log WHERE gymnast_id = ? ORDER BY updated_at DESC';
    db.query(sql, [req.params.id], (err, results) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        res.json(results);
    });
});

// Get deleted records log (admin only)
app.get('/api/deleted-logs', authenticateToken, (req, res) => {
    if (req.user.role !== 'admin') {
        return res.status(403).json({ error: 'Admin access required' });
    }
    
    const sql = 'SELECT * FROM deleted_records_log ORDER BY deleted_at DESC LIMIT 100';
    db.query(sql, (err, results) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        res.json(results);
    });
});

// Register new gymnast with user account
app.post('/api/register-gymnast-with-user', authenticateToken, async (req, res) => {
    const { full_name, email, contact_no, date_of_birth, training_program, enrollment_date, username, password } = req.body;
    
    // Validate input
    if (!full_name || !email || !contact_no || !date_of_birth || !training_program || !enrollment_date || !username || !password) {
        return res.status(400).json({ error: 'All fields are required' });
    }
    
    // Check if username exists
    const checkUserSql = 'SELECT id FROM users WHERE username = ?';
    db.query(checkUserSql, [username], async (checkErr, checkResult) => {
        if (checkErr) {
            return res.status(500).json({ error: checkErr.message });
        }
        
        if (checkResult.length > 0) {
            return res.status(400).json({ error: 'Username already exists' });
        }
        
        // Start transaction
        db.beginTransaction(async (err) => {
            if (err) {
                return res.status(500).json({ error: err.message });
            }
            
            try {
                // Generate membership ID
                const membershipId = `GYM${Date.now()}`;
                
                // Insert gymnast
                const gymnastSql = `INSERT INTO gymnasts 
                    (membership_id, full_name, email, contact_no, date_of_birth, training_program, enrollment_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)`;
                
                const gymnastResult = await new Promise((resolve, reject) => {
                    db.query(gymnastSql, [membershipId, full_name, email, contact_no, date_of_birth, training_program, enrollment_date], 
                        (err, result) => {
                            if (err) reject(err);
                            else resolve(result);
                        }
                    );
                });
                
                const gymnastId = gymnastResult.insertId;
                
                // Hash password
                const hashedPassword = await bcrypt.hash(password, 10);
                
                // Create user account
                const userSql = `INSERT INTO users 
                    (username, password, role, email, full_name, contact_no, gymnast_id) 
                    VALUES (?, ?, 'gymnast', ?, ?, ?, ?)`;
                
                await new Promise((resolve, reject) => {
                    db.query(userSql, [username, hashedPassword, email, full_name, contact_no, gymnastId], 
                        (err, result) => {
                            if (err) reject(err);
                            else resolve(result);
                        }
                    );
                });
                
                // Commit transaction
                db.commit((commitErr) => {
                    if (commitErr) {
                        return db.rollback(() => {
                            res.status(500).json({ error: commitErr.message });
                        });
                    }
                    res.status(201).json({ 
                        success: true, 
                        message: 'Gymnast registered with user account successfully',
                        membership_id: membershipId
                    });
                });
                
            } catch (error) {
                db.rollback(() => {
                    console.error('Registration error:', error);
                    res.status(500).json({ error: error.message });
                });
            }
        });
    });
});

// Dashboard stats
app.get('/api/stats', authenticateToken, (req, res) => {
    const sql = `
        SELECT 
            COUNT(*) as total_gymnasts,
            SUM(CASE WHEN progress_status = 'Active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN training_program = 'Beginner' THEN 1 ELSE 0 END) as beginner_count,
            SUM(CASE WHEN training_program = 'Intermediate' THEN 1 ELSE 0 END) as intermediate_count,
            SUM(CASE WHEN training_program = 'Advanced' THEN 1 ELSE 0 END) as advanced_count
        FROM gymnasts
    `;
    
    db.query(sql, (err, results) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        res.json(results[0]);
    });
});

// Health check endpoint
app.get('/api/health', (req, res) => {
    db.query('SELECT 1', (err) => {
        if (err) {
            return res.status(500).json({ status: 'error', message: 'Database connection failed' });
        }
        res.json({ status: 'ok', message: 'Server is running' });
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
    console.log(`API available at http://localhost:${PORT}/api`);
});