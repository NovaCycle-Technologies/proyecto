const mapaRutas = L.map('mapaRutas', { scrollWheelZoom: false }).setView([-34.907, -56.174], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(mapaRutas);

const circuitos = [
  {
    nombre: 'Circuito 42 · Parque Rodó', color: '#157347',
    puntos: [[-34.9102, -56.1610], [-34.9115, -56.1660], [-34.9125, -56.1690], [-34.9142, -56.1720], [-34.9105, -56.1740], [-34.9130, -56.1740]]
  },
  {
    nombre: 'Circuito 18 · Cordón', color: '#1769aa',
    puntos: [[-34.9018, -56.1710], [-34.9030, -56.1750], [-34.9054, -56.1770], [-34.9070, -56.1730], [-34.9056, -56.1680], [-34.9025, -56.1685]]
  },
  {
    nombre: 'Circuito 07 · Centro', color: '#a15c00',
    puntos: [[-34.8986, -56.1840], [-34.9000, -56.1880], [-34.9025, -56.1865], [-34.9040, -56.1820], [-34.9020, -56.1795], [-34.8995, -56.1810]]
  }
];

function iconoContenedor(color) {
  return L.divIcon({ className: 'icono-contenedor', html: `<span style="background:${color}"></span>`, iconSize: [14, 14], iconAnchor: [7, 7] });
}

async function dibujarCircuito(circuito) {
  const coordenadas = circuito.puntos.map(([latitud, longitud]) => `${longitud},${latitud}`).join(';');
  const respuesta = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordenadas}?overview=full&geometries=geojson`);
  if (!respuesta.ok) throw new Error('No fue posible calcular la ruta.');
  const datos = await respuesta.json();
  const geometria = datos.routes?.[0]?.geometry?.coordinates;
  if (!geometria) throw new Error('No se encontró un recorrido vial.');

  L.geoJSON({ type: 'LineString', coordinates: geometria }, { style: { color: circuito.color, weight: 5, opacity: 0.85, dashArray: '10, 6' } })
    .bindPopup(`<strong>${circuito.nombre}</strong><br>Ruta demostrativa por calles.`)
    .addTo(mapaRutas);

  circuito.puntos.forEach((parada, indice) => L.marker(parada, { icon: iconoContenedor(circuito.color) })
    .bindPopup(`<strong>${circuito.nombre}</strong><br>Contenedor de referencia ${indice + 1}`)
    .addTo(mapaRutas));

  L.circleMarker(circuito.puntos[0], { radius: 8, color: circuito.color, fillColor: circuito.color, fillOpacity: 1 })
    .bindPopup(`<strong>Inicio: ${circuito.nombre}</strong>`).addTo(mapaRutas);
}

const todosLosPuntos = circuitos.flatMap((circuito) => circuito.puntos);
mapaRutas.fitBounds(L.latLngBounds(todosLosPuntos), { padding: [24, 24] });

Promise.all(circuitos.map(dibujarCircuito)).catch(() => {
  // Muestra una alternativa legible si el servicio de ruteo no responde.
  circuitos.forEach((circuito) => L.polyline(circuito.puntos, { color: circuito.color, weight: 4, opacity: 0.75 }).addTo(mapaRutas));
});
