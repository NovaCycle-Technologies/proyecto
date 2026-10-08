const mapaRutas = L.map('mapaRutas', { scrollWheelZoom: false }).setView([-34.9011, -56.1645], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(mapaRutas);

const coloresRuta = ['#157347', '#1769aa', '#a15c00', '#7d3c98', '#b03a2e'];
const apiMapa = '../Api_Operaciones/api_operaciones.php?accion=mapa_publico';

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
  ruta.paradas.forEach((parada, indice) => L.marker(puntos[indice], { icon: iconoParada(color) })
    .bindPopup(`<strong>${textoSeguro(ruta.nombre)}</strong><br>${textoSeguro(parada.ubicacion)}<br>${textoSeguro(parada.descripcion)}`)
    .addTo(mapaRutas));

  L.circleMarker(puntos[0], { radius: 8, color, fillColor: color, fillOpacity: 1 })
    .bindPopup(`<strong>Inicio: ${textoSeguro(ruta.nombre)}</strong>`).addTo(mapaRutas);
  if (puntos.length < 2) return puntos;

  const coordenadas = puntos.map(([latitud, longitud]) => `${longitud},${latitud}`).join(';');
  const respuesta = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordenadas}?overview=full&geometries=geojson`);
  if (!respuesta.ok) throw new Error('No fue posible calcular un recorrido vial.');
  const datos = await respuesta.json();

  L.geoJSON({ type: 'LineString', coordinates: datos.routes[0].geometry.coordinates }, { style: { color, weight: 5, opacity: 0.85, dashArray: '10, 6' } })
    .bindPopup(`<strong>${textoSeguro(ruta.nombre)}</strong><br>Camión: ${textoSeguro(ruta.matricula)}`)
    .addTo(mapaRutas);

  return puntos;
}

async function cargarRutasPublicas() {
  const respuesta = await fetch(apiMapa);
  const datos = await respuesta.json();
  if (!respuesta.ok || !datos.ok) throw new Error(datos.mensaje || 'No fue posible cargar el mapa.');

  const puntos = [];
  const contenedores = datos.contenedores || [];
  const incidencias = datos.incidencias || [];
  contenedores.forEach((contenedor) => {
    const punto = [Number(contenedor.latitud), Number(contenedor.longitud)];
    L.circleMarker(punto, { radius: 6, color: '#1769aa', fillColor: '#1769aa', fillOpacity: 0.9 })
      .bindPopup(`<strong>Contenedor #${contenedor.id_contenedor}</strong><br>${textoSeguro(contenedor.ubicacion)}<br>${textoSeguro(contenedor.tipo_residuo)} · ${textoSeguro(contenedor.estado)}`)
      .addTo(mapaRutas);
    puntos.push(punto);
  });
  incidencias.forEach((incidencia) => {
    const punto = [Number(incidencia.latitud), Number(incidencia.longitud)];
    L.marker(punto, { icon: iconoParada('#b03a2e'), zIndexOffset: 1000 })
      .bindPopup(`<strong>Incidencia #${incidencia.id_incidencia}</strong><br>${textoSeguro(incidencia.tipo)} · ${textoSeguro(incidencia.estado)}<br>${textoSeguro(incidencia.ubicacion)}`)
      .addTo(mapaRutas);
    puntos.push(punto);
  });

  const rutasOperativas = datos.rutas.filter((ruta) => ruta.paradas.length >= 1);
  resumenRutasMapa.innerHTML = `<strong>Rutas visibles:</strong> ${rutasOperativas.length} · Contenedores: ${contenedores.length} · Incidencias abiertas ubicadas: ${incidencias.length}.`;
  leyendaRutasMapa.innerHTML = rutasOperativas.map((ruta, indice) => `<li><span class="punto-leyenda" style="background:${coloresRuta[indice % coloresRuta.length]}"></span> ${textoSeguro(ruta.nombre)} · Camión ${textoSeguro(ruta.matricula)}${ruta.paradas.length < 2 ? ' · Falta una parada para trazar el recorrido' : ''}</li>`).join('') || '<li>No hay rutas asignadas para hoy.</li>';
  leyendaRutasMapa.innerHTML += '<li>Azul: contenedor · Rojo: incidencia abierta</li>';

  for (const [indice, ruta] of rutasOperativas.entries()) {
    try {
      puntos.push(...await dibujarRuta(ruta, coloresRuta[indice % coloresRuta.length]));
    } catch (error) {
      leyendaRutasMapa.innerHTML += `<li>No se pudo trazar por calles ${textoSeguro(ruta.nombre)}.</li>`;
    }
  }
  if (puntos.length) mapaRutas.fitBounds(L.latLngBounds(puntos), { padding: [24, 24], maxZoom: 15 });
}
cargarRutasPublicas().catch((error) => {
  resumenRutasMapa.innerHTML = `<strong>Rutas visibles:</strong> ${textoSeguro(error.message)}`;
  leyendaRutasMapa.innerHTML = '<li>No se pudo cargar el mapa operativo.</li>';
});
