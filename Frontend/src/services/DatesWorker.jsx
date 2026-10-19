import { useEffect } from 'react';

const DatesWorker = (API_URL, token, setDatesWorker) => {
  useEffect(() => {
    const fetchWorkers = async () => {
      try {
        const response = await fetch(`${API_URL}/workers`, {
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        });
        if (!response.ok) {
          throw new Error('Error obteniendo empleados');
        }
        const result = await response.json();
        setDatesWorker(Array.isArray(result) ? result : []);
      } catch (error) {
        console.error('Error en DatesWorker:', error);
        setDatesWorker([]);
      }
    };
    if (API_URL && token) {
      fetchWorkers();
    }
  }, [API_URL, token, setDatesWorker]);
};

export default DatesWorker;
