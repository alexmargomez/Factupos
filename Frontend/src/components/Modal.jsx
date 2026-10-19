import React, { useEffect } from 'react';

const Modal = ({ show, onClose, children }) => {
  useEffect(() => {
    const handleEsc = (event) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    if (show) {
      window.addEventListener('keydown', handleEsc);
    }

    return () => {
      window.removeEventListener('keydown', handleEsc);
    };
  }, [onClose, show]);

  if (!show) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex justify-center items-center p-4"
      onClick={onClose}
    >
      <div
        className="bg-white rounded-2xl shadow-2xl relative w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-200"
        onClick={(e) => e.stopPropagation()}
      >
        {children}
      </div>
    </div>
  );
};

export default Modal;