const API_DASHBOARD = '../Api_Operaciones/api_operaciones.php?accion=dashboard_admin';
const alertasDashboard = document.getElementById('alertasDashboard');

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

function mostrarAlertas(jornada, contenedores) {
  const alertas = [
    {
      cantidad: Number(jornada.incidencias_pendientes),
      singular: 'incidencia abierta',
      plural: 'incidencias abiertas',
      detalle: 'Pendiente de revisión o cierre por el operario.'
    },
    {
      cantidad: Number(contenedores.desbordado),
      singular: 'contenedor desbordado',
      plural: 'contenedores desbordados',
      detalle: 'Se necesita atención durante la recolección.'
    },
    {
      cantidad: Number(contenedores.roto),
      singular: 'contenedor roto',
      plural: 'contenedores rotos',
      detalle: 'Se necesita mantenimiento.'
    }
  ].filter((alerta) => alerta.cantidad > 0);

  alertasDashboard.replaceChildren();
  if (alertas.length === 0) {
    const mensaje = document.createElement('p');
    mensaje.className = 'alertas-vacias';
    mensaje.textContent = 'Sin alertas activas por ahora.';
    alertasDashboard.appendChild(mensaje);
    return;
  }

  alertas.forEach((alerta) => {
    const tarjeta = document.createElement('article');
    const titulo = document.createElement('strong');
    const detalle = document.createElement('span');
    titulo.textContent = `${alerta.cantidad} ${alerta.cantidad === 1 ? alerta.singular : alerta.plural}`;
    detalle.textContent = alerta.detalle;
    tarjeta.append(titulo, detalle);
    alertasDashboard.appendChild(tarjeta);
  });
}

async function cargarDashboard() {
  try {
    const respuesta = await fetchAutenticado(API_DASHBOARD, {
      headers: { Accept: 'application/json' },
      cache: 'no-store'
    });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.ok)
      throw new Error(resultado.mensaje || 'No se pudo cargar el dashboard.');
    const { jornada, contenedores, camiones } = resultado.dashboard;
    mostrarAlertas(jornada, contenedores);
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
    mensajeDashboard.textContent = `Datos actualizados a las ${new Date().toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit' })}.`;
  } catch (error) {
    const aviso = document.createElement('p');
    aviso.className = 'alertas-error';
    aviso.textContent = `No se pudieron consultar las alertas: ${error.message}`;
    alertasDashboard.replaceChildren(aviso);
    mensajeDashboard.textContent = error.message;
  }
}

document.getElementById('btnActualizarDashboard').addEventListener('click', cargarDashboard);
cargarDashboard();
setInterval(cargarDashboard, 15000);
