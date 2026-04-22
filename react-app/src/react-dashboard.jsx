import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Link, useNavigate, useParams } from 'react-router-dom';
import axios from 'axios';


const API_BASE_URL = 'http://localhost:5000/api';
axios.defaults.baseURL = API_BASE_URL;


const getAuthToken = () => {
    const token = localStorage.getItem('token');
    return token ? `Bearer ${token}` : '';
};

// View Gymnast Component
const ViewGymnast = () => {
    const [gymnast, setGymnast] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const { id } = useParams();
    const navigate = useNavigate();

    useEffect(() => {
        fetchGymnastDetails();
    }, [id]);

    const fetchGymnastDetails = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/gymnasts/${id}`, {
                headers: { Authorization: getAuthToken() }
            });
            setGymnast(response.data);
            setError('');
        } catch (error) {
            console.error('Error fetching gymnast details:', error);
            setError('Failed to fetch gymnast details');
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div className="loading">Loading gymnast details...</div>;
    if (error) return <div className="error-message">{error}</div>;
    if (!gymnast) return <div className="error-message">Gymnast not found</div>;

    return (
        <div className="container">
            <div className="view-container">
                <div className="view-header">
                    <h2>👤 Gymnast Details</h2>
                    <div className="action-buttons">
                        <Link to="/dashboard" className="btn-secondary">← Back to Dashboard</Link>
                        <Link to={`/edit/${gymnast.id}`} className="btn-primary">✏️ Edit Gymnast</Link>
                    </div>
                </div>
                
                <div className="details-card">
                    <div className="detail-row">
                        <div className="detail-label">Membership ID:</div>
                        <div className="detail-value">{gymnast.membership_id}</div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Full Name:</div>
                        <div className="detail-value"><strong>{gymnast.full_name}</strong></div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Email:</div>
                        <div className="detail-value">{gymnast.email}</div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Contact Number:</div>
                        <div className="detail-value">{gymnast.contact_no}</div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Date of Birth:</div>
                        <div className="detail-value">{new Date(gymnast.date_of_birth).toLocaleDateString()}</div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Training Program:</div>
                        <div className="detail-value">
                            <span className={`program-badge program-${gymnast.training_program?.toLowerCase()}`}>
                                {gymnast.training_program}
                            </span>
                        </div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Enrollment Date:</div>
                        <div className="detail-value">{new Date(gymnast.enrollment_date).toLocaleDateString()}</div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Progress Status:</div>
                        <div className="detail-value">
                            <span className={`status-badge status-${gymnast.progress_status?.toLowerCase() || 'active'}`}>
                                {gymnast.progress_status || 'Active'}
                            </span>
                        </div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Created At:</div>
                        <div className="detail-value">{new Date(gymnast.created_at).toLocaleString()}</div>
                    </div>
                    <div className="detail-row">
                        <div className="detail-label">Last Updated:</div>
                        <div className="detail-value">{new Date(gymnast.updated_at).toLocaleString()}</div>
                    </div>
                </div>
            </div>
        </div>
    );
};

// Edit Gymnast Component
const EditGymnast = () => {
    const [formData, setFormData] = useState({
        full_name: '',
        email: '',
        contact_no: '',
        date_of_birth: '',
        training_program: 'Beginner',
        enrollment_date: '',
        progress_status: 'Active'
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const { id } = useParams();
    const navigate = useNavigate();

    useEffect(() => {
        fetchGymnastDetails();
    }, [id]);

    const fetchGymnastDetails = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/gymnasts/${id}`, {
                headers: { Authorization: getAuthToken() }
            });
            const gymnast = response.data;
            setFormData({
                full_name: gymnast.full_name,
                email: gymnast.email,
                contact_no: gymnast.contact_no,
                date_of_birth: gymnast.date_of_birth.split('T')[0],
                training_program: gymnast.training_program,
                enrollment_date: gymnast.enrollment_date.split('T')[0],
                progress_status: gymnast.progress_status || 'Active'
            });
        } catch (error) {
            console.error('Error fetching gymnast:', error);
            setError('Failed to fetch gymnast details');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        setSuccess('');

        try {
            const response = await axios.put(`/gymnasts/${id}`, formData, {
                headers: { Authorization: getAuthToken() }
            });

            if (response.data.success) {
                setSuccess('Gymnast updated successfully!');
                setTimeout(() => {
                    navigate('/dashboard');
                }, 1500);
            }
        } catch (error) {
            console.error('Update error:', error);
            setError(error.response?.data?.error || 'Update failed');
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    if (loading && !formData.full_name) {
        return <div className="loading">Loading gymnast data...</div>;
    }

    return (
        <div className="register-container">
            <div className="register-box">
                <h2>✏️ Edit Gymnast</h2>
                {error && <div className="error-message">{error}</div>}
                {success && <div className="success-message">{success}</div>}
                <form onSubmit={handleSubmit}>
                    <div className="form-group">
                        <label>Full Name *</label>
                        <input
                            type="text"
                            name="full_name"
                            value={formData.full_name}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Email *</label>
                        <input
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Contact Number *</label>
                        <input
                            type="tel"
                            name="contact_no"
                            value={formData.contact_no}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Date of Birth *</label>
                        <input
                            type="date"
                            name="date_of_birth"
                            value={formData.date_of_birth}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Training Program *</label>
                        <select
                            name="training_program"
                            value={formData.training_program}
                            onChange={handleChange}
                            required
                        >
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                    <div className="form-group">
                        <label>Enrollment Date *</label>
                        <input
                            type="date"
                            name="enrollment_date"
                            value={formData.enrollment_date}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Progress Status</label>
                        <select
                            name="progress_status"
                            value={formData.progress_status}
                            onChange={handleChange}
                        >
                            <option value="Active">Active</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div className="button-group">
                        <button type="submit" disabled={loading}>
                            {loading ? 'Updating...' : 'Update Gymnast'}
                        </button>
                        <button type="button" onClick={() => navigate('/dashboard')} className="btn-cancel">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

// Register Form Component
const RegisterForm = ({ onSuccess }) => {
    const [formData, setFormData] = useState({
        full_name: '',
        email: '',
        contact_no: '',
        date_of_birth: '',
        training_program: 'Beginner',
        enrollment_date: new Date().toISOString().split('T')[0]
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await axios.post('/gymnasts', formData, {
                headers: { Authorization: getAuthToken() }
            });

            if (response.data.success) {
                alert('Gymnast registered successfully!');
                navigate('/dashboard');
            }
        } catch (error) {
            console.error('Registration error:', error);
            setError(error.response?.data?.error || 'Registration failed');
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    return (
        <div className="register-container">
            <div className="register-box">
                <h2>📝 Register New Gymnast</h2>
                {error && <div className="error-message">{error}</div>}
                <form onSubmit={handleSubmit}>
                    <div className="form-group">
                        <label>Full Name *</label>
                        <input
                            type="text"
                            name="full_name"
                            value={formData.full_name}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Email *</label>
                        <input
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Contact Number *</label>
                        <input
                            type="tel"
                            name="contact_no"
                            value={formData.contact_no}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Date of Birth *</label>
                        <input
                            type="date"
                            name="date_of_birth"
                            value={formData.date_of_birth}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Training Program *</label>
                        <select
                            name="training_program"
                            value={formData.training_program}
                            onChange={handleChange}
                            required
                        >
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                    <div className="form-group">
                        <label>Enrollment Date *</label>
                        <input
                            type="date"
                            name="enrollment_date"
                            value={formData.enrollment_date}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <button type="submit" disabled={loading}>
                        {loading ? 'Registering...' : 'Register Gymnast'}
                    </button>
                    <button type="button" onClick={() => navigate('/dashboard')} className="btn-cancel">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    );
};

// Main Dashboard Component
const GymnastDashboard = () => {
    const [gymnasts, setGymnasts] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [programFilter, setProgramFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [isLoggedIn, setIsLoggedIn] = useState(false);
    const [loginData, setLoginData] = useState({ username: '', password: '' });
    const navigate = useNavigate();

    // Check login status on mount
    useEffect(() => {
        const token = localStorage.getItem('token');
        if (token) {
            setIsLoggedIn(true);
            fetchGymnasts();
        } else {
            setLoading(false);
        }
    }, []);

    // Login function
    const handleLogin = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        
        try {
            const response = await axios.post(`${API_BASE_URL}/login`, {
                username: loginData.username,
                password: loginData.password
            });
            
            if (response.data.success) {
                localStorage.setItem('token', response.data.token);
                setIsLoggedIn(true);
                await fetchGymnasts(response.data.token);
            }
        } catch (error) {
            console.error('Login error:', error);
            let errorMessage = 'Login failed. ';
            if (error.response) {
                errorMessage += error.response.data.error || 'Invalid credentials';
            } else if (error.request) {
                errorMessage += 'Cannot connect to server. Make sure backend is running on port 5000';
            }
            setError(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    // Fetch gymnasts from Express backend
    const fetchGymnasts = async (token = null) => {
        try {
            setLoading(true);
            const authToken = token || localStorage.getItem('token');
            
            const response = await axios.get('/gymnasts', {
                headers: { Authorization: `Bearer ${authToken}` }
            });
            
            setGymnasts(response.data);
            setError('');
        } catch (error) {
            console.error('Error fetching gymnasts:', error);
            if (error.response?.status === 401) {
                localStorage.removeItem('token');
                setIsLoggedIn(false);
                setError('Session expired. Please login again.');
            } else {
                setError('Failed to fetch gymnasts. Make sure server is running.');
            }
        } finally {
            setLoading(false);
        }
    };

    // Handle delete
    const handleDelete = async (id, name) => {
        if (window.confirm(`Are you sure you want to delete ${name}?`)) {
            try {
                await axios.delete(`/gymnasts/${id}`, {
                    headers: { Authorization: getAuthToken() }
                });
                await fetchGymnasts();
                alert('Gymnast deleted successfully');
            } catch (error) {
                console.error('Error deleting gymnast:', error);
                alert('Failed to delete gymnast');
            }
        }
    };

    // Search gymnasts
    const handleSearch = async () => {
        if (!searchTerm.trim()) {
            fetchGymnasts();
            return;
        }
        
        try {
            setLoading(true);
            const response = await axios.get(`/gymnasts/search/${searchTerm}`, {
                headers: { Authorization: getAuthToken() }
            });
            setGymnasts(response.data);
        } catch (error) {
            console.error('Search error:', error);
            alert('Search failed');
        } finally {
            setLoading(false);
        }
    };

    // Filtered gymnasts
    const filteredGymnasts = gymnasts.filter(gymnast => {
        const matchesSearch = gymnast.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                             gymnast.membership_id?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                             gymnast.email?.toLowerCase().includes(searchTerm.toLowerCase());
        
        const matchesProgram = !programFilter || gymnast.training_program === programFilter;
        const matchesStatus = statusFilter === 'all' || gymnast.progress_status === statusFilter;
        
        return matchesSearch && matchesProgram && matchesStatus;
    });

    // Logout function
    const handleLogout = () => {
        localStorage.removeItem('token');
        setIsLoggedIn(false);
        setGymnasts([]);
        setSearchTerm('');
        navigate('/login');
    };

    // Login Screen
    if (!isLoggedIn) {
        return (
            <div className="login-container">
                <div className="login-box">
                    <h2>🏋️ Gymnastics Academy</h2>
                    <h3>Login to Dashboard</h3>
                    {error && <div className="error-message">{error}</div>}
                    <form onSubmit={handleLogin}>
                        <div className="form-group">
                            <input
                                type="text"
                                placeholder="Username"
                                value={loginData.username}
                                onChange={(e) => setLoginData({...loginData, username: e.target.value})}
                                required
                            />
                        </div>
                        <div className="form-group">
                            <input
                                type="password"
                                placeholder="Password"
                                value={loginData.password}
                                onChange={(e) => setLoginData({...loginData, password: e.target.value})}
                                required
                            />
                        </div>
                        <button type="submit" disabled={loading}>
                            {loading ? 'Logging in...' : 'Login'}
                        </button>
                    </form>
                </div>
            </div>
        );
    }

    // Loading state
    if (loading && gymnasts.length === 0) {
        return <div className="loading">Loading gymnasts...</div>;
    }

    // Main Dashboard
    return (
        <div className="dashboard-container">
            {/* Navigation Bar with Links */}
            <nav className="navbar">
                <div className="nav-brand">
                    <h2>🤸 Gymnastics Academy</h2>
                </div>
                <div className="nav-links">
                    <Link to="/dashboard" className="nav-link active">
                        🏠 Dashboard
                    </Link>
                    <Link to="/register" className="nav-link">
                        ➕ Register Gymnast
                    </Link>
                    <Link to="/profile" className="nav-link">
                        👤 My Profile
                    </Link>
                    <button onClick={handleLogout} className="nav-logout">
                        🚪 Logout
                    </button>
                </div>
            </nav>

            <div className="dashboard-header">
                <h1>🤸 Gymnast Management Dashboard</h1>
                <div className="stats-summary">
                    <div className="stat-card">
                        <h3>{gymnasts.length}</h3>
                        <p>Total Gymnasts</p>
                    </div>
                    <div className="stat-card">
                        <h3>{gymnasts.filter(g => g.progress_status === 'Active').length}</h3>
                        <p>Active</p>
                    </div>
                    <div className="stat-card">
                        <h3>{gymnasts.filter(g => g.training_program === 'Beginner').length}</h3>
                        <p>Beginners</p>
                    </div>
                </div>
            </div>

            {/* Search and Filter Section */}
            <div className="filters-section">
                <div className="search-group">
                    <input
                        type="text"
                        placeholder="Search by name, membership ID, or email..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="search-input"
                    />
                    <button onClick={handleSearch} className="btn-search">
                        🔍 Search
                    </button>
                </div>
                
                <select 
                    value={programFilter} 
                    onChange={(e) => setProgramFilter(e.target.value)}
                    className="filter-select"
                >
                    <option value="">All Programs</option>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                </select>

                <div className="status-filters">
                    {['all', 'Active', 'On Hold', 'Completed'].map(status => (
                        <button
                            key={status}
                            className={`status-filter-btn ${statusFilter === status ? 'active' : ''}`}
                            onClick={() => setStatusFilter(status)}
                        >
                            {status === 'all' ? 'All' : status}
                        </button>
                    ))}
                </div>
            </div>

            {/* Gymnasts Table */}
            <div className="table-container">
                {filteredGymnasts.length === 0 ? (
                    <p className="no-data">No gymnasts found</p>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Membership ID</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Program</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredGymnasts.map(gymnast => (
                                <tr key={gymnast.id}>
                                    <td><strong>{gymnast.full_name}</strong></td>
                                    <td>{gymnast.membership_id}</td>
                                    <td>{gymnast.email}</td>
                                    <td>{gymnast.contact_no || 'N/A'}</td>
                                    <td>{gymnast.training_program}</td>
                                    <td>
                                        <span className={`status-badge status-${gymnast.progress_status?.toLowerCase() || 'active'}`}>
                                            {gymnast.progress_status || 'Active'}
                                        </span>
                                    </td>
                                    <td className="action-buttons">
                                        <Link to={`/gymnast/${gymnast.id}`} className="btn-info">
                                            👁️ View
                                        </Link>
                                        <Link to={`/edit/${gymnast.id}`} className="btn-primary">
                                            ✏️ Edit
                                        </Link>
                                        <button 
                                            className="btn-danger"
                                            onClick={() => handleDelete(gymnast.id, gymnast.full_name)}
                                        >
                                            🗑️ Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
};

// Main App Component with Routes
const App = () => {
    return (
        <Router>
            <div className="app">
                <Routes>
                    <Route path="/" element={<GymnastDashboard />} />
                    <Route path="/login" element={<GymnastDashboard />} />
                    <Route path="/dashboard" element={<GymnastDashboard />} />
                    <Route path="/register" element={<RegisterForm />} />
                    <Route path="/gymnast/:id" element={<ViewGymnast />} />
                    <Route path="/edit/:id" element={<EditGymnast />} />
                    <Route path="/profile" element={<div className="container"><h2>Profile Page</h2><Link to="/dashboard">Back to Dashboard</Link></div>} />
                </Routes>
            </div>
        </Router>
    );
};

export default App;