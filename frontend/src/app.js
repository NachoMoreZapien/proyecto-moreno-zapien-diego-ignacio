document.addEventListener("DOMContentLoaded", () => {
    const API_URL = '/api/incidencias';
    cargarIncidencias();

    // Crear Incidencia
    document.getElementById('formIncidencia').addEventListener('submit', async (e) => {
        e.preventDefault();
        const titulo = document.getElementById('titulo').value;
        const descripcion = document.getElementById('descripcion').value;

        await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ titulo, descripcion })
        });

        document.getElementById('formIncidencia').reset();
        alert('Incidencia creada. Se ha enviado un correo a Mailpit.');
        cargarIncidencias();
    });

    // Cargar Listado
    async function cargarIncidencias() {
        const res = await fetch(API_URL);
        const json = await res.json();
        
        document.getElementById('visitas').innerText = `Visitas al listado (Redis): ${json.visitas}`;
        document.getElementById('origen').innerText = `Datos obtenidos de: ${json.origen.toUpperCase()}`;

        const tbody = document.getElementById('listaIncidencias');
        tbody.innerHTML = '';
        
        json.datos.forEach(inc => {
            const colorEstado = inc.estado === 'resuelta' ? 'text-green-600' : (inc.estado === 'en_progreso' ? 'text-yellow-600' : 'text-red-600');
            
            tbody.innerHTML += `
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 border">${inc.id}</td>
                    <td class="p-3 border font-medium">${inc.titulo}</td>
                    <td class="p-3 border text-gray-600 text-sm">${inc.descripcion}</td>
                    <td class="p-3 border font-bold ${colorEstado}">${inc.estado.toUpperCase()}</td>
                    <td class="p-3 border text-center space-x-2">
                        <select onchange="cambiarEstado(${inc.id}, this.value)" class="p-1 border rounded text-sm">
                            <option value="">Cambiar...</option>
                            <option value="abierta">Abierta</option>
                            <option value="en_progreso">En Progreso</option>
                            <option value="resuelta">Resuelta</option>
                        </select>
                        <button onclick="eliminar(${inc.id})" class="bg-red-500 text-white px-2 py-1 rounded text-sm hover:bg-red-600">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    }

    // Actualizar Estado
    window.cambiarEstado = async (id, estado) => {
        if(!estado) return;
        await fetch(`${API_URL}/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ estado })
        });
        cargarIncidencias();
    };

    // Eliminar Incidencia
    window.eliminar = async (id) => {
        if(confirm('¿Estás seguro de eliminar esta incidencia?')) {
            await fetch(`${API_URL}/${id}`, { method: 'DELETE' });
            cargarIncidencias();
        }
    };
});