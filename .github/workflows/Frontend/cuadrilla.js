const API_OPERACIONES = '../Api_Operaciones/vista.php';
const usuario = JSON.parse(sessionStorage.getItem('usuarioNovaCycle') || 'null');
let asignacionActual = null;

async function api(accion, datos = null) { const opciones = { headers: { Accept: 'application/json' } }; if (datos) { opciones.method = 'POST'; opciones.headers['Content-Type'] = 'application/json'; opciones.body = JSON.stringify(datos); } const respuesta = await fetch(`${API_OPERACIONES}?accion=${accion}`, opciones); const resultado = await respuesta.json(); if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || 'No se pudo completar la operación.'); return resultado; }
function seguro(texto) { const nodo = document.createElement('span'); nodo.textContent = texto; return nodo.innerHTML; }
async function cargarRuta() {
  const { asignacion } = await api('mi_ruta');
  if (!asignacion) {
    nombreRuta.textContent = 'Sin ruta asignada';
    zonaRuta.textContent = 'El administrador debe asignarte una ruta para hoy.';
    camionAsignado.textContent = 'Sin camión asignado';
    estadoCamionResumen.textContent = '';
    paradasPendientes.textContent = '0 pendientes';
    detalleParadas.textContent = '0 de 0 completadas';
    progreso.textContent = '0 / 0';
    listaParadas.innerHTML = '<li>No tenés una ruta asignada para hoy.</li>';
    return;
  }
  asignacionActual = asignacion; nombreRuta.textContent = asignacion.nombre; zonaRuta.textContent = asignacion.zona; camionAsignado.textContent = asignacion.matricula; estadoCamionResumen.textContent = `${asignacion.modelo} · ${asignacion.estado.replaceAll('_', ' ')}`;
  const hechas = asignacion.paradas.filter((p) => Number(p.completada)).length; paradasPendientes.textContent = `${asignacion.paradas.length - hechas} pendientes`; detalleParadas.textContent = `${hechas} de ${asignacion.paradas.length} completadas`; progreso.textContent = `${hechas} / ${asignacion.paradas.length}`;
  listaParadas.innerHTML = asignacion.paradas.map((p) => `<li class="${Number(p.completada) ? 'completada' : ''}"><div><strong>${seguro(p.ubicacion)}</strong><span>${seguro(p.descripcion)}</span></div>${Number(p.completada) ? '<span>Completada</span>' : `<button type="button" class="btn-completar" data-id="${p.id_parada}">Completar</button>`}</li>`).join('') || '<li>Esta ruta no tiene paradas.</li>';
  document.querySelectorAll('.btn-completar').forEach((boton) => boton.addEventListener('click', async () => { try { alert((await api('completar_parada', { id_asignacion: asignacionActual.id_asignacion, id_parada: boton.dataset.id })).mensaje); cargarRuta(); } catch (error) { alert(error.message); } }));
}
if (!usuario || !['peon', 'conductor'].includes(usuario.rol)) window.location.replace('login.html'); else { saludo.textContent = `Hola, ${usuario.nombre}`; panelCamion.hidden = usuario.rol !== 'conductor'; cargarRuta().catch((error) => { listaParadas.innerHTML = `<li>${seguro(error.message)}</li>`; }); }
formIncidencia.addEventListener('submit', async (evento) => { evento.preventDefault(); try { alert((await api('incidencia', { ubicacion: ubicacion.value, tipo: tipoIncidencia.value, detalle: detalle.value })).mensaje); evento.currentTarget.reset(); } catch (error) { alert(error.message); } });
formCamion.addEventListener('submit', async (evento) => { evento.preventDefault(); if (!asignacionActual) return alert('No tenés un camión asignado.'); try { alert((await api('estado_camion', { id_camion: asignacionActual.id_camion, estado: estadoCamion.value })).mensaje); cargarRuta(); } catch (error) { alert(error.message); } });
