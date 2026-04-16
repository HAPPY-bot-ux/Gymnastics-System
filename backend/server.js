const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 5000;

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
    host: 'localhost',
    user: 'root',
    password: 'Narutostorm4',
    database: 'gymnastics_academy'
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
    
    jwt.verify(token, 'your-secret-key', (err, user) => {
        if (err) return res.status(403).json({ error: 'Invalid token' });
        req.user = user;
        next();
    });
};

// Routes
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

// Update gymnast
app.put('/api/gymnasts/:id', authenticateToken, (req, res) => {
    const { full_name, email, contact_no, date_of_birth, training_program, enrollment_date, progress_status } = req.body;
    
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

// Delete gymnast
app.delete('/api/gymnasts/:id', authenticateToken, (req, res) => {
    // First get gymnast data for logging
    const selectSql = 'SELECT * FROM gymnasts WHERE id = ?';
    db.query(selectSql, [req.params.id], (err, result) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        if (result.length === 0) {
            return res.status(404).json({ error: 'Gymnast not found' });
        }
        
        const gymnast = result[0];
        const deleteSql = 'DELETE FROM gymnasts WHERE id = ?';
        
        db.query(deleteSql, [req.params.id], (err, deleteResult) => {
            if (err) {
                return res.status(500).json({ error: err.message });
            }
            
            // Log deletion
            const logEntry = `[${new Date().toISOString()}] Deleted gymnast: ${gymnast.full_name} (ID: ${gymnast.id})\n`;
            fs.appendFileSync('deletion_log.txt', logEntry);
            
            res.json({ success: true, message: 'Gymnast deleted successfully' });
        });
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

// Login endpoint
app.post('/api/login', (req, res) => {
    const { username, password } = req.body;
    
    const sql = 'SELECT * FROM users WHERE username = ?';
    db.query(sql, [username], async (err, results) => {
        if (err) {
            return res.status(500).json({ error: err.message });
        }
        
        if (results.length === 0) {
            return res.status(401).json({ error: 'Invalid credentials' });
        }
        
        const user = results[0];
        const validPassword = await bcrypt.compare(password, user.password);
        
        if (!validPassword) {
            return res.status(401).json({ error: 'Invalid credentials' });
        }
        
        const token = jwt.sign(
            { id: user.id, username: user.username, role: user.role },
            'your-secret-key',
            { expiresIn: '24h' }
        );
        
        res.json({ 
            success: true, 
            token, 
            user: { id: user.id, username: user.username, role: user.role } 
        });
    });
});

app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});