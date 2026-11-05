function fetchAutenticado(url, opciones = {}) {
  const pestana = sessionStorage.getItem('pestanaNovaCycle');
  const headers = new Headers(opciones.headers || {});
  if (pestana) headers.set('X-NovaCycle-Tab', pestana);
  try {
    const rol = JSON.parse(sessionStorage.getItem('usuarioNovaCycle') || 'null')?.rol;
    if (rol) headers.set('X-NovaCycle-Rol', rol);
  } catch (error) {
    // Una sesión local dañada no debe interrumpir la petición.
  }
  return fetch(url, { ...opciones, headers });
}

document.querySelectorAll('[data-cerrar-sesion]').forEach((enlace) => {
  enlace.addEventListener('click', async (evento) => {
    evento.preventDefault();
    try {
      const respuesta = await fetchAutenticado('../Api_Usuarios/api_usuarios.php?accion=cerrar_sesion', {
        method: 'POST',
        headers: { Accept: 'application/json' }
      });
      if (!respuesta.ok) throw new Error('No se pudo cerrar la sesión.');
      sessionStorage.removeItem('usuarioNovaCycle');
      sessionStorage.removeItem('pestanaNovaCycle');
      window.location.replace('index.html');
    } catch (error) {
      alert(error.message);
    }
  });
});
