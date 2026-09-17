const mapaRutas = L.map('mapaRutas', { scrollWheelZoom: false }).setView([-34.9011, -56.1645], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(mapaRutas);

const coloresRuta = ['#157347', '#1769aa', '#a15c00', '#7d3c98', '#b03a2e'];
const apiMapa = '../Api_Operaciones/api.php?accion=mapa_publico';

function textoSeguro(valor) {
  const elemento = document.createElement('span');
  elemento.textContent = valor;
  return elemento.innerHTML;
}

function iconoParada(color) {
  return L.divIcon({ className: 'icono-contenedor', html: `<span style="background:${color}"></span>`, iconSize: [14, 14], iconAnchor: [7, 7] });
}

async function dibujarRuta(ruta, color) {
  const puntos = ruta.paradas.map((parada) => [Number(parada.latitud), Number(parada.longitud)]);
  if (puntos.length < 2) return [];

  const coordenadas = puntos.map(([latitud, longitud]) => `${longitud},${latitud}`).join(';');
  const respuesta = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordenadas}?overview=full&geometries=geojson`);
  if (!respuesta.ok) throw new Error('No fue posible calcular un recorrido vial.');
  const datos = await respuesta.json();

  L.geoJSON({ type: 'LineString', coordinates: datos.routes[0].geometry.coordinates }, { style: { color, weight: 5, opacity: 0.85, dashArray: '10, 6' } })
    .bindPopup(`<strong>${textoSeguro(ruta.nombre)}</strong><br>Camión: ${textoSeguro(ruta.matricula)}`)
    .addTo(mapaRutas);

  ruta.paradas.forEach((parada, indice) => L.marker(puntos[indice], { icon: iconoParada(color) })
    .bindPopup(`<strong>${textoSeguro(ruta.nombre)}</strong><br>${textoSeguro(parada.ubicacion)}<br>${textoSeguro(parada.descripcion)}`)
    .addTo(mapaRutas));

  L.circleMarker(puntos[0], { radius: 8, color, fillColor: color, fillOpacity: 1 })
    .bindPopup(`<strong>Inicio: ${textoSeguro(ruta.nombre)}</strong>`).addTo(mapaRutas);
  return puntos;
}

async function cargarRutasPublicas() {
  const respuesta = await fetch(apiMapa);
  const datos = await respuesta.json();
  if (!respuesta.ok || !datos.ok) throw new Error(datos.mensaje || 'No fue posible cargar las rutas.');

  const rutasConParadas = datos.rutas.filter((ruta) => ruta.paradas.length >= 2);
  if (!rutasConParadas.length) {
    resumenRutasMapa.innerHTML = '<strong>Rutas visibles:</strong> no hay rutas operativas para hoy.';
    leyendaRutasMapa.innerHTML = '<li>El administrador debe asignar una ruta y cargar al menos dos paradas con coordenadas.</li>';
    return;
  }

  resumenRutasMapa.innerHTML = `<strong>Rutas visibles:</strong> ${rutasConParadas.length} circuito(s) operativo(s).`;
  leyendaRutasMapa.innerHTML = rutasConParadas.map((ruta, indice) => `<li><span class="punto-leyenda" style="background:${coloresRuta[indice % coloresRuta.length]}"></span> ${textoSeguro(ruta.nombre)} · Camión ${textoSeguro(ruta.matricula)}</li>`).join('');
  const puntos = (await Promise.all(rutasConParadas.map((ruta, indice) => dibujarRuta(ruta, coloresRuta[indice % coloresRuta.length])))).flat();
  if (puntos.length) mapaRutas.fitBounds(L.latLngBounds(puntos), { padding: [24, 24] });
}

cargarRutasPublicas().catch((error) => {
  resumenRutasMapa.innerHTML = `<strong>Rutas visibles:</strong> ${textoSeguro(error.message)}`;
  leyendaRutasMapa.innerHTML = '<li>No se pudo cargar el mapa operativo.</li>';
});
