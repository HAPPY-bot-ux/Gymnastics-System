import React from 'react';
import ReactDOM from 'react-dom/client';
import './app.css';  // Import your CSS file
import App from './react-dashboard';  // Import your main component

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);