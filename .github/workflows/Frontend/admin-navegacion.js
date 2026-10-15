const seccionesAdmin = {
  resumen: {
    titulo: 'Resumen general',
    descripcion: 'Alertas y datos de la jornada en un solo lugar.'
  },
  usuarios: {
    titulo: 'Usuarios',
    descripcion: 'Revisá solicitudes y administrá las cuentas registradas.'
  },
  contenedores: {
    titulo: 'Contenedores',
    descripcion: 'Consultá, agregá y actualizá los puntos de recolección.'
  },
  camiones: {
    titulo: 'Camiones',
    descripcion: 'Gestioná la flota y su disponibilidad.'
  },
  rutas: {
    titulo: 'Rutas y cuadrillas',
    descripcion: 'Organizá recorridos, contenedores y trabajadores.'
  }
};

const enlacesAdmin = document.querySelectorAll('.navegacion-admin a[data-seccion]');
const panelesAdmin = document.querySelectorAll('main > .panel-admin');

function mostrarSeccionAdmin() {
  const hash = window.location.hash.slice(1).replace(/^vista-/, '');
  const seccion = hash === 'alertas' || hash === 'reportes' ? 'resumen' : hash;
  const activa = seccionesAdmin[seccion] ? seccion : 'resumen';

  document.getElementById('tituloSeccion').textContent = seccionesAdmin[activa].titulo;
  document.getElementById('descripcionSeccion').textContent = seccionesAdmin[activa].descripcion;

  panelesAdmin.forEach((panel) => {
    panel.classList.toggle('activo', panel.dataset.panel === activa);
  });
  enlacesAdmin.forEach((enlace) => {
    const seleccionado = enlace.dataset.seccion === activa;
    enlace.classList.toggle('activo', seleccionado);
    if (seleccionado) enlace.setAttribute('aria-current', 'page');
    else enlace.removeAttribute('aria-current');
  });

  document.body.classList.add('paneles-activos');
  requestAnimationFrame(() => window.scrollTo(0, 0));
}

window.addEventListener('hashchange', mostrarSeccionAdmin);
enlacesAdmin.forEach((enlace) => {
  enlace.addEventListener('click', () => {
    if (window.location.hash === enlace.getAttribute('href')) mostrarSeccionAdmin();
  });
});
mostrarSeccionAdmin();
