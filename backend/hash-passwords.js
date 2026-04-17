const bcrypt = require('bcrypt');
const mysql = require('mysql2');
require('dotenv').config();

async function fixPasswords() {
    // Database connection
    const db = mysql.createConnection({
        host: process.env.DB_HOST || 'localhost',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || 'Narutostorm4',
        database: process.env.DB_NAME || 'gymnastics_academy'
    });

    // Hash passwords
    const adminHash = await bcrypt.hash('admin123', 10);
    const gymnastHash = await bcrypt.hash('gymnast123', 10);
    
    console.log('Generated hashes:');
    console.log('Admin hash:', adminHash);
    console.log('Gymnast hash:', gymnastHash);
    
    // Update admin password
    db.query('UPDATE users SET password = ? WHERE username = ?', [adminHash, 'admin'], (err, result) => {
        if (err) {
            console.error('Error updating admin:', err);
        } else {
            console.log(`Admin password updated. Affected rows: ${result.affectedRows}`);
        }
    });
    
    // Update gymnast password
    db.query('UPDATE users SET password = ? WHERE username = ?', [gymnastHash, 'gymnast1'], (err, result) => {
        if (err) {
            console.error('Error updating gymnast:', err);
        } else {
            console.log(`Gymnast password updated. Affected rows: ${result.affectedRows}`);
        }
        db.end();
    });
}

fixPasswords();