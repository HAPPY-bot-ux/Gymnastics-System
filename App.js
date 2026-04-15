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
    ScrollView
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';

const API_URL = 'http://your-server-ip:5000/api';

const App = () => {
    const [gymnasts, setGymnasts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [token, setToken] = useState(null);
    const [isLoggedIn, setIsLoggedIn] = useState(false);
    const [loginData, setLoginData] = useState({ username: '', password: '' });

    useEffect(() => {
        checkLoginStatus();
    }, []);

    const checkLoginStatus = async () => {
        const storedToken = await AsyncStorage.getItem('token');
        if (storedToken) {
            setToken(storedToken);
            setIsLoggedIn(true);
            fetchGymnasts(storedToken);
        } else {
            setLoading(false);
        }
    };

    const handleLogin = async () => {
        try {
            const response = await axios.post(`${API_URL}/login`, loginData);
            if (response.data.success) {
                await AsyncStorage.setItem('token', response.data.token);
                setToken(response.data.token);
                setIsLoggedIn(true);
                fetchGymnasts(response.data.token);
            }
        } catch (error) {
            Alert.alert('Login Failed', 'Invalid credentials');
        }
    };

    const fetchGymnasts = async (authToken) => {
        try {
            const response = await axios.get(`${API_URL}/gymnasts`, {
                headers: { Authorization: `Bearer ${authToken}` }
            });
            setGymnasts(response.data);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching gymnasts:', error);
            setLoading(false);
        }
    };

    const handleDelete = (id) => {
        Alert.alert(
            'Delete Gymnast',
            'Are you sure you want to delete this gymnast?',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Delete',
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            await axios.delete(`${API_URL}/gymnasts/${id}`, {
                                headers: { Authorization: `Bearer ${token}` }
                            });
                            fetchGymnasts(token);
                        } catch (error) {
                            Alert.alert('Error', 'Failed to delete gymnast');
                        }
                    }
                }
            ]
        );
    };

    const handleLogout = async () => {
        await AsyncStorage.removeItem('token');
        setToken(null);
        setIsLoggedIn(false);
        setGymnasts([]);
    };

    const filteredGymnasts = gymnasts.filter(gymnast =>
        gymnast.full_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        gymnast.membership_id.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const renderGymnastCard = ({ item }) => (
        <View style={styles.card}>
            <View style={styles.cardHeader}>
                <Text style={styles.cardTitle}>{item.full_name}</Text>
                <Text style={styles.membershipId}>{item.membership_id}</Text>
            </View>
            <View style={styles.cardBody}>
                <Text style={styles.cardText}>Email: {item.email}</Text>
                <Text style={styles.cardText}>Program: {item.training_program}</Text>
                <View style={[styles.statusBadge, 
                    item.progress_status === 'Active' && styles.statusActive,
                    item.progress_status === 'On Hold' && styles.statusOnHold,
                    item.progress_status === 'Completed' && styles.statusCompleted
                ]}>
                    <Text style={styles.statusText}>{item.progress_status}</Text>
                </View>
            </View>
            <View style={styles.cardActions}>
                <TouchableOpacity 
                    style={[styles.button, styles.buttonView]}
                    onPress={() => Alert.alert('Profile', `Viewing ${item.full_name}'s profile`)}
                >
                    <Text style={styles.buttonText}>View</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                    style={[styles.button, styles.buttonDelete]}
                    onPress={() => handleDelete(item.id)}
                >
                    <Text style={styles.buttonText}>Delete</Text>
                </TouchableOpacity>
            </View>
        </View>
    );

    if (!isLoggedIn) {
        return (
            <ScrollView style={styles.container}>
                <View style={styles.loginContainer}>
                    <Text style={styles.title}>Gymnastics Academy</Text>
                    <Text style={styles.subtitle}>Login to continue</Text>
                    
                    <TextInput
                        style={styles.input}
                        placeholder="Username"
                        value={loginData.username}
                        onChangeText={(text) => setLoginData({ ...loginData, username: text })}
                    />
                    
                    <TextInput
                        style={styles.input}
                        placeholder="Password"
                        secureTextEntry
                        value={loginData.password}
                        onChangeText={(text) => setLoginData({ ...loginData, password: text })}
                    />
                    
                    <TouchableOpacity style={styles.loginButton} onPress={handleLogin}>
                        <Text style={styles.loginButtonText}>Login</Text>
                    </TouchableOpacity>
                </View>
            </ScrollView>
        );
    }

    if (loading) {
        return (
            <View style={styles.loadingContainer}>
                <ActivityIndicator size="large" color="#667eea" />
                <Text>Loading gymnasts...</Text>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.headerTitle}>Gymnasts Dashboard</Text>
                <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
                    <Text style={styles.logoutText}>Logout</Text>
                </TouchableOpacity>
            </View>
            
            <TextInput
                style={styles.searchInput}
                placeholder="Search gymnasts..."
                value={searchTerm}
                onChangeText={setSearchTerm}
            />
            
            <FlatList
                data={filteredGymnasts}
                renderItem={renderGymnastCard}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContainer}
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f5f5f5',
    },
    loadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    loginContainer: {
        flex: 1,
        justifyContent: 'center',
        padding: 20,
        marginTop: 100,
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
        backgroundColor: 'white',
        padding: 15,
        borderRadius: 8,
        marginBottom: 15,
        borderWidth: 1,
        borderColor: '#ddd',
    },
    loginButton: {
        backgroundColor: '#667eea',
        padding: 15,
        borderRadius: 8,
        alignItems: 'center',
    },
    loginButtonText: {
        color: 'white',
        fontSize: 16,
        fontWeight: 'bold',
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 20,
        backgroundColor: '#667eea',
    },
    headerTitle: {
        fontSize: 20,
        fontWeight: 'bold',
        color: 'white',
    },
    logoutButton: {
        backgroundColor: 'rgba(255,255,255,0.2)',
        padding: 8,
        borderRadius: 6,
    },
    logoutText: {
        color: 'white',
    },
    searchInput: {
        backgroundColor: 'white',
        margin: 15,
        padding: 12,
        borderRadius: 8,
        borderWidth: 1,
        borderColor: '#ddd',
    },
    listContainer: {
        padding: 15,
    },
    card: {
        backgroundColor: 'white',
        borderRadius: 8,
        padding: 15,
        marginBottom: 15,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
        elevation: 3,
    },
    cardHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 10,
    },
    cardTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
    },
    membershipId: {
        fontSize: 12,
        color: '#666',
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
        paddingHorizontal: 10,
        paddingVertical: 4,
        borderRadius: 4,
        marginTop: 5,
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
        fontWeight: '500',
    },
    cardActions: {
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
    button: {
        flex: 1,
        padding: 10,
        borderRadius: 6,
        alignItems: 'center',
        marginHorizontal: 5,
    },
    buttonView: {
        backgroundColor: '#17a2b8',
    },
    buttonDelete: {
        backgroundColor: '#dc3545',
    },
    buttonText: {
        color: 'white',
        fontWeight: '500',
    },
});

export default App;