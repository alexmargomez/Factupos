import React, { useEffect } from 'react';
import { FaCheck } from "react-icons/fa";
import '../styles/ModalReady.css';

const ModalReady = ({ show, onClose, Date: messageText }) => {
  useEffect(() => {
    const handleEsc = (event) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    let timer;
    if (show) {
      window.addEventListener('keydown', handleEsc);
      timer = setTimeout(() => {
        onClose();
      }, 2200);
    }
    
    return () => {
      window.removeEventListener('keydown', handleEsc);
      clearTimeout(timer);
    };
  }, [show, onClose]);

  if (!show) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex justify-center items-center p-4"
      onClick={onClose}
    >
      <div
        className="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-sm flex justify-center items-center flex-col text-center border border-emerald-100"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center mb-4">
          <FaCheck className="text-5xl text-emerald-500 icon-check" />
        </div>
        <h2 className="text-xl font-extrabold text-[#023047]">
          ¡PROCESO {messageText || 'COMPLETADO'}!
        </h2>
        <p className="text-sm text-slate-500 mt-1">
          La operación se registró correctamente
        </p>
      </div>
    </div>
  );
};

export default ModalReady;