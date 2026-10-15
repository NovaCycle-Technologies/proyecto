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
