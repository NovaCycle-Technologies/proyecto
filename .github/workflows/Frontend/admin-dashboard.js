const API_DASHBOARD = '../Api_Operaciones/api_operaciones.php?accion=dashboard_admin';

function escaparDashboard(valor) {
  const elemento = document.createElement('span');
  elemento.textContent = valor;
  return elemento.innerHTML;
}

function dibujarBarras(elemento, datos, etiquetas) {
  const total = Math.max(...Object.values(datos), 1);
  elemento.innerHTML = Object.entries(datos)
    .map(
      ([clave, cantidad]) =>
        `<div class="fila-barra"><span>${escaparDashboard(etiquetas[clave] || clave)}</span><div class="pista-barra"><div class="valor-barra" style="width:${(Number(cantidad) / total) * 100}%"></div></div><strong>${cantidad}</strong></div>`
    )
    .join('');
}

async function cargarDashboard() {
  try {
    const respuesta = await fetch(API_DASHBOARD, { headers: { Accept: 'application/json' } });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.ok)
      throw new Error(resultado.mensaje || 'No se pudo cargar el dashboard.');
    const { jornada, contenedores, camiones } = resultado.dashboard;
    dashboardMetricas.innerHTML = [
      ['Rutas asignadas hoy', jornada.rutas_hoy],
      ['Paradas completadas', jornada.recolecciones_hoy],
      ['Incidencias pendientes', jornada.incidencias_pendientes],
      ['Residuos ingresados', `${Number(jornada.ingresos_hoy).toLocaleString('es-UY')} kg`]
    ]
      .map(
        ([titulo, valor]) => `<article><span>${titulo}</span><strong>${valor}</strong></article>`
      )
      .join('');
    dibujarBarras(graficoContenedores, contenedores, {
      funcional: 'Funcionales',
      roto: 'Rotos',
      desbordado: 'Desbordados'
    });
    dibujarBarras(graficoCamiones, camiones, {
      disponible: 'Disponibles',
      en_mantenimiento: 'Mantenimiento',
      fuera_de_servicio: 'Fuera de servicio'
    });
    mensajeDashboard.textContent = 'Datos actualizados al cargar la página.';
  } catch (error) {
    mensajeDashboard.textContent = error.message;
  }
}

document.getElementById('btnActualizarDashboard').addEventListener('click', cargarDashboard);
cargarDashboard();
