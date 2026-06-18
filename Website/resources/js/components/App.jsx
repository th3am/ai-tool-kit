import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import OtpVerification from './pages/auth/OtpVerification';

const App = () => {
    return (
        <Router>
            <Routes>
                <Route path="/login" element={<Login />} />
                <Route path="/register" element={<Register />} />
                <Route path="/verify-otp" element={<OtpVerification />} />
                <Route path="/" element={<Login />} />
            </Routes>
        </Router>
    );
};

export default App;
