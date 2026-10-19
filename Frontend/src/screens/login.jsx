import { LoginForm } from "../components/LoginVal";
import Factupos from "../assets/factupos.svg";
import Electrimotos from "../assets/electrimotos.png";

function LoginScreen() {
  return (
    <div className="min-h-screen w-full bg-gradient-to-br from-[#023047] via-[#03405e] to-[#011c29] flex flex-col justify-between">
      <main className="flex-1 flex flex-col lg:flex-row items-center justify-center gap-10 px-6 py-10 max-w-6xl mx-auto w-full">
        {/* Formulario de Inicio de Sesión */}
        <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-white/20 p-8 flex flex-col items-center">
          <div className="w-24 h-24 mb-3 flex items-center justify-center bg-slate-50 rounded-2xl p-3 shadow-inner border border-slate-100">
            <img src={Factupos} alt="Factupos Icon" className="w-full h-full object-contain" />
          </div>
          <h1 className="font-extrabold text-3xl text-[#023047] text-center">
            Iniciar Sesión
          </h1>
          <p className="text-slate-500 text-sm text-center mt-1 mb-6">
            Ingresa tus credenciales para acceder a FactuPOS
          </p>
          <LoginForm />
        </div>

        {/* Branding Electrimotos */}
        <div className="hidden lg:flex flex-col items-center justify-center w-1/2 p-6 text-center">
          <img
            src={Electrimotos}
            alt="Electrimotos Logo"
            className="max-w-md w-full object-contain drop-shadow-2xl"
          />
          <p className="text-sky-200/90 mt-6 text-lg font-medium max-w-md">
            Sistema integral de facturación POS, control de inventario y agendamiento de taller.
          </p>
        </div>
      </main>

      <footer className="py-4 px-6 border-t border-white/10">
        <p className="text-slate-300 text-xs sm:text-sm text-center">
          © 2025 Alexmar Gómez Software. Todos los derechos reservados. | Plataforma de facturación POS - FACTUPOS.
        </p>
      </footer>
    </div>
  );
}

export default LoginScreen;
