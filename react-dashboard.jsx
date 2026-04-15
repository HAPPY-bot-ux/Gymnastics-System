import React, { useState, useEffect } from 'react';
import axios from 'axios';

// Functional Component with Hooks
const GymnastDashboard = () => {
    const [gymnasts, setGymnasts] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [programFilter, setProgramFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchGymnasts();
    }, []);

    const fetchGymnasts = async () => {
        try {
            const response = await axios.get('/api/gymnasts.php');
            setGymnasts(response.data);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching gymnasts:', error);
            setLoading(false);
        }
    };

    // Filtered gymnasts based on search and filters
    const filteredGymnasts = gymnasts.filter(gymnast => {
        const matchesSearch = gymnast.full_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                             gymnast.membership_id.toLowerCase().includes(searchTerm.toLowerCase()) ||
                             gymnast.email.toLowerCase().includes(searchTerm.toLowerCase());
        
        const matchesProgram = !programFilter || gymnast.training_program === programFilter;
        const matchesStatus = statusFilter === 'all' || gymnast.progress_status === statusFilter;
        
        return matchesSearch && matchesProgram && matchesStatus;
    });

    // Sort gymnasts by name
    const sortGymnasts = (key) => {
        const sorted = [...filteredGymnasts].sort((a, b) => 
            a[key].localeCompare(b[key])
        );
        setGymnasts(sorted);
    };

    // Handle delete with confirmation
    const handleDelete = async (id) => {
        if (window.confirm('Are you sure you want to delete this gymnast?')) {
            try {
                await axios.delete(`/api/gymnasts.php?id=${id}`);
                fetchGymnasts();
            } catch (error) {
                console.error('Error deleting gymnast:', error);
            }
        }
    };

    if (loading) return <div className="loading">Loading...</div>;

    return (
        <div className="dashboard-container">
            <div className="dashboard-header">
                <h1><i className="fas fa-users"></i> Gymnast Management Dashboard</h1>
                <button className="btn-primary" onClick={() => window.location.href='/register.php'}>
                    <i className="fas fa-user-plus"></i> Register New Gymnast
                </button>
            </div>

            {/* Search and Filter Section */}
            <div className="filters-section">
                <input
                    type="text"
                    placeholder="Search by name, membership ID, or email..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="search-input"
                />
                
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
                <table className="data-table">
                    <thead>
                        <tr>
                            <th onClick={() => sortGymnasts('full_name')}>Name <i className="fas fa-sort"></i></th>
                            <th>Membership ID</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredGymnasts.map(gymnast => (
                            <tr key={gymnast.id}>
                                <td>{gymnast.full_name}</td>
                                <td>{gymnast.membership_id}</td>
                                <td>{gymnast.email}</td>
                                <td>{gymnast.training_program}</td>
                                <td>
                                    <span className={`status-badge status-${gymnast.progress_status.toLowerCase().replace(' ', '')}`}>
                                        {gymnast.progress_status}
                                    </span>
                                </td>
                                <td className="action-buttons">
                                    <button 
                                        className="btn-info"
                                        onClick={() => window.location.href=`/profile.php?id=${gymnast.id}`}
                                    >
                                        <i className="fas fa-eye"></i> View
                                    </button>
                                    <button 
                                        className="btn-primary"
                                        onClick={() => window.location.href=`/update.php?id=${gymnast.id}`}
                                    >
                                        <i className="fas fa-edit"></i> Update
                                    </button>
                                    <button 
                                        className="btn-danger"
                                        onClick={() => handleDelete(gymnast.id)}
                                    >
                                        <i className="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

// Class-based Component for Comparison (Legacy Style)
class GymnastForm extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            full_name: '',
            email: '',
            contact_no: '',
            training_program: 'Beginner',
            errors: {}
        };
    }

    handleChange = (e) => {
        this.setState({ [e.target.name]: e.target.value });
    };

    handleSubmit = async (e) => {
        e.preventDefault();
        // Form submission logic
        try {
            const response = await axios.post('/api/register.php', this.state);
            if (response.data.success) {
                alert('Gymnast registered successfully!');
                this.props.onSuccess();
            }
        } catch (error) {
            this.setState({ errors: error.response.data.errors });
        }
    };

    render() {
        return (
            <form onSubmit={this.handleSubmit} className="gymnast-form">
                <div className="form-group">
                    <label>Full Name:</label>
                    <input
                        type="text"
                        name="full_name"
                        value={this.state.full_name}
                        onChange={this.handleChange}
                        required
                    />
                    {this.state.errors.full_name && <span className="error">{this.state.errors.full_name}</span>}
                </div>
                
                <div className="form-group">
                    <label>Email:</label>
                    <input
                        type="email"
                        name="email"
                        value={this.state.email}
                        onChange={this.handleChange}
                        required
                    />
                </div>
                
                <div className="form-group">
                    <label>Contact Number:</label>
                    <input
                        type="tel"
                        name="contact_no"
                        value={this.state.contact_no}
                        onChange={this.handleChange}
                        required
                    />
                </div>
                
                <div className="form-group">
                    <label>Training Program:</label>
                    <select
                        name="training_program"
                        value={this.state.training_program}
                        onChange={this.handleChange}
                    >
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>
                
                <button type="submit" className="btn-submit">Register Gymnast</button>
            </form>
        );
    }
}

// Comparison: Functional vs Class-based Components
/*
Functional Components (Modern React):
- Use Hooks (useState, useEffect) for state management
- Simpler and more concise
- Easier to test and debug
- Better performance with React.memo
- No 'this' binding issues

Class-based Components (Legacy):
- Use this.state and this.setState()
- Lifecycle methods (componentDidMount, componentDidUpdate)
- More verbose
- Require binding event handlers
- Still supported but not recommended for new projects
*/

export default GymnastDashboard;