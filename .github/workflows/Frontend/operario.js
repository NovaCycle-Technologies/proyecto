const API_OPERACIONES = '../Api_Operaciones/api_operaciones.php';

const usuarioOperario = JSON.parse(sessionStorage.getItem('usuarioNovaCycle') || 'null');

async function api(accion, datos = null) { const opciones = { headers: { Accept: 'application/json' } };
 if (datos) { opciones.method = 'POST';
 opciones.headers['Content-Type'] = 'application/json'; 
 opciones.body = JSON.stringify(datos);  } 
 const respuesta = await fetch(`${API_OPERACIONES}?accion=${accion}`, opciones);
  const resultado = await respuesta.json();
   if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || 'No se pudo completar la operación.'); 
   return resultado; }
function textoSeguro(valor) { const nodo = document.createElement('span');
 nodo.textContent = valor; return nodo.innerHTML; }
function cargarCamiones() { return api('camiones').then(({ camiones }) => { camion.innerHTML = camiones.map((c) => `<option value="${c.id_camion}">${textoSeguro(c.matricula)} · ${textoSeguro(c.modelo)}</option>`).join('') || '<option value="">No hay camiones</option>'; tablaCamiones.innerHTML = camiones.map((c) => `<tr><td>${textoSeguro(c.matricula)}</td><td>${textoSeguro(c.modelo)}</td><td>${textoSeguro(c.estado.replaceAll('_', ' '))}</td></tr>`).join('') || '<tr><td colspan="3">No hay camiones activos.</td></tr>'; }); }
function cargarResumen() { return api('resumen').then(({ resumen }) => { totalIngresos.textContent = `${Number(resumen.ingresos_hoy).toLocaleString('es-UY')} kg`; camionesRecibidos.textContent = `${resumen.camiones_hoy} camiones recibidos`; totalIncidencias.textContent = resumen.incidencias_pendientes; }); }
function cargarCerradas() {
  return api('incidencias&estado=cerradas').then(({ incidencias }) => {
    listaIncidenciasCerradas.innerHTML = incidencias.map((i) => `<li><div><strong>#${i.id_incidencia} · ${textoSeguro(i.tipo)} · ${textoSeguro(i.ubicacion)}</strong><span>${textoSeguro(i.detalle)} · Cerrada: ${textoSeguro(i.fecha_cierre || '')}</span></div></li>`).join('') || '<li>No hay incidencias cerradas.</li>';
  });
}
function cargarIncidencias() {
  return api('incidencias&estado=abiertas').then(({ incidencias }) => {
    listaIncidencias.innerHTML = incidencias.map((i) => {
      const revisar = i.estado === 'pendiente' ? `<button type="button" data-accion="revisar_incidencia" data-id="${i.id_incidencia}">Marcar revisada</button>` : '';
      return `<li><div><strong>#${i.id_incidencia} · ${textoSeguro(i.tipo)} · ${textoSeguro(i.ubicacion)}</strong><span>${textoSeguro(i.detalle)} · ${i.estado === 'pendiente' ? 'Pendiente' : 'Revisada'}</span></div><div>${revisar}<button type="button" data-accion="cerrar_incidencia" data-id="${i.id_incidencia}">Cerrar</button></div></li>`;
    }).join('') || '<li>No hay incidencias abiertas.</li>';
    listaIncidencias.querySelectorAll('button[data-accion]').forEach((boton) => boton.addEventListener('click', async () => {
      try {
        alert((await api(boton.dataset.accion, { id: boton.dataset.id })).mensaje);
        await Promise.all([cargarIncidencias(), cargarResumen()]);
      } catch (error) { alert(error.message); }
    }));
    return cargarCerradas();
  });
}
if (!usuarioOperario || usuarioOperario.rol !== 'operario') { window.location.replace('login.html'); } else { saludo.textContent = `Hola, ${usuarioOperario.nombre}`; Promise.all([cargarCamiones(), cargarResumen(), cargarIncidencias()]).catch((error) => alert(error.message)); }
formIngreso.addEventListener('submit', async (evento) => { evento.preventDefault(); try { alert((await api('ingreso', { id_camion: camion.value, tipo_residuo: tipoResiduo.value, peso_kg: peso.value })).mensaje); evento.currentTarget.reset(); cargarResumen(); } catch (error) { alert(error.message); } });
formMaquinaria.addEventListener('submit', async (evento) => { evento.preventDefault(); try { alert((await api('maquinaria', { maquinaria: maquinaria.value, estado: estadoMaquinaria.value, observacion: observacion.value })).mensaje); evento.currentTarget.reset(); } catch (error) { alert(error.message); } });
