const { exec, spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const { platform } = require('os');

console.log('\x1b[36m%s\x1b[0m', '═══════════════════════════════════════════════════');
console.log('\x1b[36m%s\x1b[0m', '     GYMNASTICS ACADEMY MANAGEMENT SYSTEM');
console.log('\x1b[36m%s\x1b[0m', '═══════════════════════════════════════════════════\n');

const colors = {
    reset: '\x1b[0m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    cyan: '\x1b[36m',
    red: '\x1b[31m'
};

console.log(`${colors.yellow}Starting Backend Server...${colors.reset}\n`);

// Start backend
const backend = spawn('node', ['backend/server.js'], {
    stdio: 'pipe',
    shell: true
});

backend.stdout.on('data', (data) => {
    console.log(`${colors.green}[BACKEND]${colors.reset} ${data.toString().trim()}`);
});

backend.stderr.on('data', (data) => {
    console.log(`${colors.red}[BACKEND ERROR]${colors.reset} ${data.toString().trim()}`);
});

setTimeout(() => {
    console.log(`\n${colors.yellow}═══════════════════════════════════════════════════${colors.reset}`);
    console.log(`${colors.green}✓ Backend server is running on http://localhost:5000${colors.reset}`);
    console.log(`${colors.yellow}═══════════════════════════════════════════════════\n${colors.reset}`);
    
    console.log(`${colors.cyan}Starting Web App...${colors.reset}\n`);
    
    // Start React web app
    const webApp = spawn('npm', ['start'], {
        cwd: path.join(__dirname, 'react-app'),
        stdio: 'inherit',
        shell: true
    });
    
    console.log(`${colors.cyan}Starting Mobile App...${colors.reset}\n`);
    console.log(`${colors.blue}To start React Native:${colors.reset}`);
    console.log(`${colors.blue}1. Open a new terminal${colors.reset}`);
    console.log(`${colors.blue}2. Run: cd react-native-app && npm start${colors.reset}`);
    console.log(`${colors.blue}3. Press 'a' for Android or 'i' for iOS${colors.reset}\n`);
    
    console.log(`${colors.green}Web app will open automatically in your browser...${colors.reset}`);
    
    // Open browser after 5 seconds
    setTimeout(() => {
        const url = 'http://localhost:3000';
        const command = platform() === 'win32' ? 'start' : (platform() === 'darwin' ? 'open' : 'xdg-open');
        exec(`${command} ${url}`);
    }, 5000);
    
}, 5000);

process.on('SIGINT', () => {
    console.log(`\n${colors.yellow}Shutting down servers...${colors.reset}`);
    backend.kill();
    process.exit();
});