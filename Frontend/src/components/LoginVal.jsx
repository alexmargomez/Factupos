import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

const LoginForm = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const [name, setName] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const login = async (name, password) => {
    setLoading(true);
    setError('');
    try {
      const response = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, password }),
      });

      const data = await response.json();
      if (response.ok) {
        const token = data.access_token;
        localStorage.setItem('authToken', token);
        navigate('/dashboard');
      } else {
        setError(data.message || 'Credenciales inválidas. Verifica usuario y contraseña.');
      }
    } catch (err) {
      console.error('Error al hacer la solicitud', err);
      setError('No se pudo conectar con el servidor. Verifica que la API esté activa.');
    } finally {
      setLoading(false);
    }
  };

  const handleLogin = (event) => {
    event.preventDefault();
    if (name.trim() && password) {
      login(name.trim(), password);
    } else {
      setError('Por favor, ingresa tu usuario y contraseña');
    }
  };

  return (
    <div className="w-full">
      <form onSubmit={handleLogin} className="space-y-4">
        <div>
          <label htmlFor="name" className="block mb-1.5 text-sm font-semibold text-slate-700">
            Nombre de usuario
          </label>
          <input
            type="text"
            id="name"
            name="name"
            placeholder="Ej. admin"
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#219ebc] focus:border-transparent transition"
            required
          />
        </div>
        <div>
          <label htmlFor="password" className="block mb-1.5 text-sm font-semibold text-slate-700">
            Contraseña
          </label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#219ebc] focus:border-transparent transition"
            required
          />
        </div>
        <button
          type="submit"
          disabled={loading}
          className="w-full py-3 mt-2 bg-[#023047] hover:bg-[#034466] text-white font-bold rounded-xl shadow-md transition disabled:opacity-60"
        >
          {loading ? 'Ingresando...' : 'Ingresar'}
        </button>
      </form>
      {error && (
        <div className="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm text-center font-medium">
          {error}
        </div>
      )}
    </div>
  );
};

const Logout = (navigate) => async () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const token = localStorage.getItem('authToken');

  try {
    if (token) {
      await fetch(`${API_URL}/logout`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
    }
  } catch (error) {
    console.error('Error cerrando sesión en el servidor:', error);
  } finally {
    localStorage.removeItem('authToken');
    navigate('/');
  }
};

export { LoginForm, Logout };
