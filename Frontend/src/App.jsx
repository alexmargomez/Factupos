import { BrowserRouter as Router, Routes, Route, useLocation } from 'react-router-dom';
import './App.css';
import Login from './screens/login';
import Dashboard from './screens/dashboard';
import Navbar from './components/NavBar';
import PrivateRoute from './components/PrivateRoute';
import Look from './screens/look';
import Sale from './screens/sale'
import Inventory from './screens/inventory';
import Schedule from './screens/schedule';
import Report from './screens/report';


function App() {
  return (
    <Router>
      <Main />
    </Router>
  );
}

function Main() {
  const location = useLocation();
  const isLoginPage = location.pathname === '/';

  return (
    <div className="min-h-screen w-full flex flex-col md:flex-row bg-slate-100">
      {!isLoginPage && <Navbar />}

      <main className="flex-1 min-w-0 min-h-screen overflow-x-hidden">
        <Routes>
          <Route path="/" element={<Login />} />
          <Route path="/dashboard" element={<PrivateRoute element={<Dashboard />} />} />
          <Route path="/look" element={<PrivateRoute element={<Look />} />} />
          <Route path="/sales" element={<PrivateRoute element={<Sale />} />} />
          <Route path="/inventory" element={<PrivateRoute element={<Inventory />} />} />
          <Route path="/schedule" element={<PrivateRoute element={<Schedule />} />} />
          <Route path="/reports" element={<PrivateRoute element={<Report />} />} />
        </Routes>
      </main>
    </div>
  );
}

export default App;

