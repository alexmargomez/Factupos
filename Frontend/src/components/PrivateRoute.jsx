import React from 'react';
import { Navigate } from 'react-router-dom';

const PrivateRoute = ({ element, ...rest }) => {
  const token = localStorage.getItem('authToken');

  // Si no hay token, redirige al login
  return token ? element : <Navigate to="/" replace />;
};

export default PrivateRoute;
