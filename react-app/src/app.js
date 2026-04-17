import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    TextInput,
    TouchableOpacity,
    Alert,
    ActivityIndicator,
    ScrollView,
    RefreshControl,
    Modal
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';

// IMPORTANT: Change this to your actual computer's IP address
// For Android emulator: http://10.0.2.2:5000/api
// For iOS simulator: http://localhost:5000/api
// For physical device: http://YOUR_COMPUTER_IP:5000/api
const API_URL = 'http://10.0.2.2:5000/api';

const App = () => {
    const [gymnasts, setGymnasts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [token, setToken] = useState(null);
    const [isLoggedIn, setIsLoggedIn] = useState(false);
    const [loginData, setLoginData] = useState({ username: '', password: '' });
    const [showRegisterModal, setShowRegisterModal] = useState(false);
    const [newGymnast, setNewGymnast] = useState({
        full_name: '',
        email: '',
        contact_no: '',
        date_of_birth: '',
        training_program: 'Beginner',
        enrollment_date: new Date().toISOString().split('T')[0]
    });

    useEffect(() => {
        checkLoginStatus();
    }, []);

    const checkLoginStatus = async () => {
        try {
            const storedToken = await AsyncStorage.getItem('token');
            if (storedToken) {
                setToken(storedToken);
                setIsLoggedIn(true);
                await fetchGymnasts(storedToken);
            } else {
                setLoading(false);
            }
        } catch (error) {
            console.error('Error checking login status:', error);
            setLoading(false);
        }
    };

    const handleLogin = async () => {
        if (!loginData.username || !loginData.password) {
            Alert.alert('Error', 'Please enter username and password');
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post(`${API_URL}/login`, {
                username: loginData.username,
                password: loginData.password
            });
            
            if (response.data.success) {
                await AsyncStorage.setItem('token', response.data.token);
                setToken(response.data.token);
                setIsLoggedIn(true);
                await fetchGymnasts(response.data.token);
            }
        } catch (error) {
            console.error('Login error:', error);
            let errorMessage = 'Login failed';
            if (error.response) {
                errorMessage = error.response.data.error || 'Invalid credentials';
            } else if (error.request) {
                errorMessage = 'Cannot connect to server. Check if backend is running.';
            }
            Alert.alert('Login Failed', errorMessage);
        } finally {
            setLoading(false);
        }
    };

    const fetchGymnasts = async (authToken) => {
        try {
            const response = await axios.get(`${API_URL}/gymnasts`, {
                headers: { Authorization: `Bearer ${authToken}` }
            });
            setGymnasts(response.data);
        } catch (error) {
            console.error('Error fetching gymnasts:', error);
            if (error.response?.status === 401) {
                await handleLogout();
                Alert.alert('Session Expired', 'Please login again');
            } else {
                Alert.alert('Error', 'Failed to fetch gymnasts');
            }
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchGymnasts(token);
    };

    const handleDelete = (id, name) => {
        Alert.alert(
            'Delete Gymnast',
            `Are you sure you want to delete ${name}?`,
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Delete',
                    style: 'destructive',
                    onPress: async () => {
                        setLoading(true);
                        try {
                            await axios.delete(`${API_URL}/gymnasts/${id}`, {
                                headers: { Authorization: `Bearer ${token}` }
                            });
                            await fetchGymnasts(token);
                            Alert.alert('Success', 'Gymnast deleted successfully');
                        } catch (error) {
                            console.error('Delete error:', error);
                            Alert.alert('Error', 'Failed to delete gymnast');
                        } finally {
                            setLoading(false);
                        }
                    }
                }
            ]
        );
    };

    const handleRegister = async () => {
        if (!newGymnast.full_name || !newGymnast.email || !newGymnast.contact_no || !newGymnast.date_of_birth) {
            Alert.alert('Error', 'Please fill all required fields');
            return;
        }

        setLoading(true);
        try {
            await axios.post(`${API_URL}/gymnasts`, newGymnast, {
                headers: { Authorization: `Bearer ${token}` }
            });
            Alert.alert('Success', 'Gymnast registered successfully');
            setShowRegisterModal(false);
            setNewGymnast({
                full_name: '',
                email: '',
                contact_no: '',
                date_of_birth: '',
                training_program: 'Beginner',
                enrollment_date: new Date().toISOString().split('T')[0]
            });
            await fetchGymnasts(token);
        } catch (error) {
            console.error('Registration error:', error);
            Alert.alert('Error', 'Failed to register gymnast');
        } finally {
            setLoading(false);
        }
    };

    const handleLogout = async () => {
        await AsyncStorage.removeItem('token');
        setToken(null);
        setIsLoggedIn(false);
        setGymnasts([]);
        setLoginData({ username: '', password: '' });
    };

    const filteredGymnasts = gymnasts.filter(gymnast =>
        gymnast.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        gymnast.membership_id?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        gymnast.email?.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const renderGymnastCard = ({ item }) => (
        <View style={styles.card}>
            <View style={styles.cardHeader}>
                <Text style={styles.cardTitle}>{item.full_name}</Text>
                <Text style={styles.membershipId}>{item.membership_id}</Text>
            </View>
            <View style={styles.cardBody}>
                <Text style={styles.cardText}>📧 {item.email}</Text>
                <Text style={styles.cardText}>📞 {item.contact_no || 'N/A'}</Text>
                <Text style={styles.cardText}>🎯 Program: {item.training_program}</Text>
                <Text style={styles.cardText}>📅 Enrolled: {new Date(item.enrollment_date).toLocaleDateString()}</Text>
                <View style={[
                    styles.statusBadge, 
                    item.progress_status === 'Active' && styles.statusActive,
                    item.progress_status === 'On Hold' && styles.statusOnHold,
                    item.progress_status === 'Completed' && styles.statusCompleted
                ]}>
                    <Text style={styles.statusText}>
                        {item.progress_status || 'Active'}
                    </Text>
                </View>
            </View>
            <View style={styles.cardActions}>
                <TouchableOpacity 
                    style={[styles.button, styles.buttonView]}
                    onPress={() => Alert.alert(
                        'Gymnast Details',
                        `Name: ${item.full_name}\n` +
                        `Membership ID: ${item.membership_id}\n` +
                        `Email: ${item.email}\n` +
                        `Contact: ${item.contact_no}\n` +
                        `Program: ${item.training_program}\n` +
                        `Status: ${item.progress_status || 'Active'}`
                    )}
                >
                    <Text style={styles.buttonText}>View Details</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                    style={[styles.button, styles.buttonDelete]}
                    onPress={() => handleDelete(item.id, item.full_name)}
                >
                    <Text style={styles.buttonText}>Delete</Text>
                </TouchableOpacity>
            </View>
        </View>
    );

    // Login Screen
    if (!isLoggedIn) {
        return (
            <ScrollView 
                style={styles.container}
                contentContainerStyle={styles.loginScrollContainer}
                keyboardShouldPersistTaps="handled"
            >
                <View style={styles.loginContainer}>
                    <Text style={styles.title}>🤸 Gymnastics Academy</Text>
                    <Text style={styles.subtitle}>Login to manage gymnasts</Text>
                    
                    <TextInput
                        style={styles.input}
                        placeholder="Username"
                        placeholderTextColor="#999"
                        value={loginData.username}
                        onChangeText={(text) => setLoginData({ ...loginData, username: text })}
                        autoCapitalize="none"
                    />
                    
                    <TextInput
                        style={styles.input}
                        placeholder="Password"
                        placeholderTextColor="#999"
                        secureTextEntry
                        value={loginData.password}
                        onChangeText={(text) => setLoginData({ ...loginData, password: text })}
                    />
                    
                    <TouchableOpacity 
                        style={styles.loginButton} 
                        onPress={handleLogin}
                        disabled={loading}
                    >
                        {loading ? (
                            <ActivityIndicator color="white" />
                        ) : (
                            <Text style={styles.loginButtonText}>Login</Text>
                        )}
                    </TouchableOpacity>
                    
                    <View style={styles.demoCredentials}>
                        <Text style={styles.demoText}>Demo Credentials:</Text>
                        <Text style={styles.demoText}>Username: admin</Text>
                        <Text style={styles.demoText}>Password: admin123</Text>
                    </View>
                </View>
            </ScrollView>
        );
    }

    // Loading Screen
    if (loading && gymnasts.length === 0) {
        return (
            <View style={styles.loadingContainer}>
                <ActivityIndicator size="large" color="#667eea" />
                <Text style={styles.loadingText}>Loading gymnasts...</Text>
            </View>
        );
    }

    // Main Dashboard
    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.headerTitle}>Gymnasts Dashboard</Text>
                    <Text style={styles.headerSubtitle}>Total: {filteredGymnasts.length}</Text>
                </View>
                <View style={styles.headerButtons}>
                    <TouchableOpacity onPress={() => setShowRegisterModal(true)} style={styles.addButton}>
                        <Text style={styles.addButtonText}>+ Add</Text>
                    </TouchableOpacity>
                    <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
                        <Text style={styles.logoutText}>Logout</Text>
                    </TouchableOpacity>
                </View>
            </View>
            
            <View style={styles.searchContainer}>
                <TextInput
                    style={styles.searchInput}
                    placeholder="🔍 Search by name, ID, or email..."
                    placeholderTextColor="#999"
                    value={searchTerm}
                    onChangeText={setSearchTerm}
                />
            </View>
            
            <FlatList
                data={filteredGymnasts}
                renderItem={renderGymnastCard}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContainer}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={onRefresh}
                        colors={["#667eea"]}
                    />
                }
                ListEmptyComponent={
                    <View style={styles.emptyContainer}>
                        <Text style={styles.emptyText}>No gymnasts found</Text>
                    </View>
                }
            />

            {/* Register Modal */}
            <Modal
                animationType="slide"
                transparent={true}
                visible={showRegisterModal}
                onRequestClose={() => setShowRegisterModal(false)}
            >
                <View style={styles.modalContainer}>
                    <ScrollView style={styles.modalContent}>
                        <Text style={styles.modalTitle}>Register New Gymnast</Text>
                        
                        <TextInput
                            style={styles.modalInput}
                            placeholder="Full Name *"
                            value={newGymnast.full_name}
                            onChangeText={(text) => setNewGymnast({...newGymnast, full_name: text})}
                        />
                        
                        <TextInput
                            style={styles.modalInput}
                            placeholder="Email *"
                            keyboardType="email-address"
                            value={newGymnast.email}
                            onChangeText={(text) => setNewGymnast({...newGymnast, email: text})}
                        />
                        
                        <TextInput
                            style={styles.modalInput}
                            placeholder="Contact Number *"
                            keyboardType="phone-pad"
                            value={newGymnast.contact_no}
                            onChangeText={(text) => setNewGymnast({...newGymnast, contact_no: text})}
                        />
                        
                        <TextInput
                            style={styles.modalInput}
                            placeholder="Date of Birth (YYYY-MM-DD) *"
                            value={newGymnast.date_of_birth}
                            onChangeText={(text) => setNewGymnast({...newGymnast, date_of_birth: text})}
                        />
                        
                        <View style={styles.pickerContainer}>
                            <Text style={styles.pickerLabel}>Training Program:</Text>
                            {['Beginner', 'Intermediate', 'Advanced'].map(program => (
                                <TouchableOpacity
                                    key={program}
                                    style={[styles.pickerOption, newGymnast.training_program === program && styles.pickerOptionSelected]}
                                    onPress={() => setNewGymnast({...newGymnast, training_program: program})}
                                >
                                    <Text style={[styles.pickerOptionText, newGymnast.training_program === program && styles.pickerOptionTextSelected]}>
                                        {program}
                                    </Text>
                                </TouchableOpacity>
                            ))}
                        </View>
                        
                        <View style={styles.modalButtons}>
                            <TouchableOpacity 
                                style={[styles.modalButton, styles.modalButtonCancel]}
                                onPress={() => setShowRegisterModal(false)}
                            >
                                <Text style={styles.modalButtonText}>Cancel</Text>
                            </TouchableOpacity>
                            <TouchableOpacity 
                                style={[styles.modalButton, styles.modalButtonSubmit]}
                                onPress={handleRegister}
                            >
                                <Text style={styles.modalButtonText}>Register</Text>
                            </TouchableOpacity>
                        </View>
                    </ScrollView>
                </View>
            </Modal>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f5f5f5',
    },
    loginScrollContainer: {
        flexGrow: 1,
        justifyContent: 'center',
    },
    loadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: '#f5f5f5',
    },
    loadingText: {
        marginTop: 10,
        fontSize: 16,
        color: '#666',
    },
    loginContainer: {
        padding: 20,
        backgroundColor: 'white',
        margin: 20,
        borderRadius: 12,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
        elevation: 5,
    },
    title: {
        fontSize: 32,
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#667eea',
        marginBottom: 10,
    },
    subtitle: {
        fontSize: 16,
        textAlign: 'center',
        color: '#666',
        marginBottom: 30,
    },
    input: {
        backgroundColor: '#f8f9fa',
        padding: 15,
        borderRadius: 8,
        marginBottom: 15,
        borderWidth: 1,
        borderColor: '#e0e0e0',
        fontSize: 16,
    },
    loginButton: {
        backgroundColor: '#667eea',
        padding: 15,
        borderRadius: 8,
        alignItems: 'center',
        marginTop: 10,
    },
    loginButtonText: {
        color: 'white',
        fontSize: 18,
        fontWeight: 'bold',
    },
    demoCredentials: {
        marginTop: 20,
        padding: 15,
        backgroundColor: '#f8f9fa',
        borderRadius: 8,
        alignItems: 'center',
    },
    demoText: {
        fontSize: 12,
        color: '#666',
        marginVertical: 2,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 20,
        backgroundColor: '#667eea',
        borderBottomLeftRadius: 20,
        borderBottomRightRadius: 20,
    },
    headerTitle: {
        fontSize: 20,
        fontWeight: 'bold',
        color: 'white',
    },
    headerSubtitle: {
        fontSize: 12,
        color: 'rgba(255,255,255,0.8)',
        marginTop: 4,
    },
    headerButtons: {
        flexDirection: 'row',
        gap: 10,
    },
    addButton: {
        backgroundColor: '#28a745',
        paddingVertical: 8,
        paddingHorizontal: 16,
        borderRadius: 8,
        marginRight: 10,
    },
    addButtonText: {
        color: 'white',
        fontWeight: '600',
    },
    logoutButton: {
        backgroundColor: 'rgba(255,255,255,0.2)',
        paddingVertical: 8,
        paddingHorizontal: 16,
        borderRadius: 8,
    },
    logoutText: {
        color: 'white',
        fontWeight: '600',
    },
    searchContainer: {
        padding: 15,
    },
    searchInput: {
        backgroundColor: 'white',
        padding: 12,
        borderRadius: 8,
        borderWidth: 1,
        borderColor: '#e0e0e0',
        fontSize: 16,
    },
    listContainer: {
        padding: 15,
    },
    card: {
        backgroundColor: 'white',
        borderRadius: 12,
        padding: 15,
        marginBottom: 15,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 6,
        elevation: 3,
    },
    cardHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 10,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
        paddingBottom: 10,
    },
    cardTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
    },
    membershipId: {
        fontSize: 12,
        color: '#667eea',
        fontWeight: '600',
    },
    cardBody: {
        marginBottom: 15,
    },
    cardText: {
        fontSize: 14,
        color: '#555',
        marginBottom: 5,
    },
    statusBadge: {
        alignSelf: 'flex-start',
        paddingHorizontal: 12,
        paddingVertical: 6,
        borderRadius: 6,
        marginTop: 8,
    },
    statusActive: {
        backgroundColor: '#d4edda',
    },
    statusOnHold: {
        backgroundColor: '#fff3cd',
    },
    statusCompleted: {
        backgroundColor: '#d1ecf1',
    },
    statusText: {
        fontSize: 12,
        fontWeight: '600',
        color: '#333',
    },
    cardActions: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        gap: 10,
    },
    button: {
        flex: 1,
        paddingVertical: 10,
        borderRadius: 8,
        alignItems: 'center',
    },
    buttonView: {
        backgroundColor: '#17a2b8',
    },
    buttonDelete: {
        backgroundColor: '#dc3545',
    },
    buttonText: {
        color: 'white',
        fontWeight: '600',
        fontSize: 14,
    },
    emptyContainer: {
        padding: 40,
        alignItems: 'center',
    },
    emptyText: {
        fontSize: 16,
        color: '#999',
    },
    modalContainer: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'center',
        padding: 20,
    },
    modalContent: {
        backgroundColor: 'white',
        borderRadius: 12,
        padding: 20,
        maxHeight: '80%',
    },
    modalTitle: {
        fontSize: 24,
        fontWeight: 'bold',
        color: '#667eea',
        textAlign: 'center',
        marginBottom: 20,
    },
    modalInput: {
        backgroundColor: '#f8f9fa',
        padding: 12,
        borderRadius: 8,
        marginBottom: 15,
        borderWidth: 1,
        borderColor: '#e0e0e0',
        fontSize: 14,
    },
    pickerContainer: {
        marginBottom: 20,
    },
    pickerLabel: {
        fontSize: 14,
        fontWeight: '600',
        color: '#333',
        marginBottom: 10,
    },
    pickerOption: {
        padding: 10,
        backgroundColor: '#f8f9fa',
        borderRadius: 8,
        marginBottom: 5,
    },
    pickerOptionSelected: {
        backgroundColor: '#667eea',
    },
    pickerOptionText: {
        color: '#333',
    },
    pickerOptionTextSelected: {
        color: 'white',
    },
    modalButtons: {
        flexDirection: 'row',
        gap: 10,
        marginTop: 20,
    },
    modalButton: {
        flex: 1,
        padding: 12,
        borderRadius: 8,
        alignItems: 'center',
    },
    modalButtonCancel: {
        backgroundColor: '#6c757d',
    },
    modalButtonSubmit: {
        backgroundColor: '#28a745',
    },
    modalButtonText: {
        color: 'white',
        fontWeight: '600',
    },    
});

export default App;



