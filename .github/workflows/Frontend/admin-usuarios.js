const tablaUsuariosRegistrados = document.getElementById('tablaUsuariosRegistrados');
const cuerpoUsuariosRegistrados = tablaUsuariosRegistrados.querySelector('tbody');
const mensajeUsuariosRegistrados = document.getElementById('mensajeUsuariosRegistrados');
const formUsuario = document.getElementById('formUsuario');

function cerrarEdicionUsuario() {
  formUsuario.reset();
  formUsuario.hidden = true;
}

function editarUsuario(usuario) {
  formUsuario.hidden = false;
  document.getElementById('ciUsuario').value = usuario.ci;
  document.getElementById('nombreUsuario').value = usuario.nombre;
  document.getElementById('apellidoUsuario').value = usuario.apellido;
  document.getElementById('emailUsuario').value = usuario.email;
  document.getElementById('rolUsuario').value = usuario.rol || usuario.rol_solicitado;
  formUsuario.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function cargarUsuariosRegistrados() {
  try {
    const respuesta = await fetchAutenticado(apiUsuarios + '?accion=listar_usuarios', {
      method: 'POST',
      headers: { Accept: 'application/json' }
    });
    const datos = await respuesta.json();
    if (!respuesta.ok || !datos.ok) throw new Error(datos.mensaje || 'No se pudieron cargar los usuarios.');

    cuerpoUsuariosRegistrados.replaceChildren();
    tablaUsuariosRegistrados.hidden = datos.usuarios.length === 0;
    mensajeUsuariosRegistrados.textContent = datos.usuarios.length ? '' : 'No hay usuarios registrados.';

    datos.usuarios.forEach((usuario) => {
      const fila = document.createElement('tr');
      const rol = usuario.rol || usuario.rol_solicitado;
      fila.innerHTML = `
        <td>${textoSeguro(usuario.ci)}</td>
        <td>${textoSeguro(usuario.nombre)} ${textoSeguro(usuario.apellido)}</td>
        <td>${textoSeguro(usuario.email)}</td>
        <td>${textoSeguro(usuario.estado_cuenta)}</td>
        <td>${textoSeguro(nombreRol(rol))}</td>
      `;

      const acciones = document.createElement('td');
      if (usuario.rol === 'admin_municipal') {
        acciones.textContent = 'Cuenta protegida';
      } else {
        const editar = document.createElement('button');
        editar.type = 'button';
        editar.textContent = 'Modificar';
        editar.addEventListener('click', () => editarUsuario(usuario));

        const eliminar = document.createElement('button');
        eliminar.type = 'button';
        eliminar.textContent = 'Eliminar';
        eliminar.addEventListener('click', () => eliminarUsuarioRegistrado(usuario));
        acciones.append(editar, eliminar);
      }
      fila.appendChild(acciones);
      cuerpoUsuariosRegistrados.appendChild(fila);
    });
  } catch (error) {
    tablaUsuariosRegistrados.hidden = true;
    mensajeUsuariosRegistrados.textContent = error.message;
  }
}

async function eliminarUsuarioRegistrado(usuario) {
  if (!confirm(`¿Eliminar la cuenta de ${usuario.nombre} ${usuario.apellido} (${usuario.ci})? Esta acción no se puede deshacer.`)) {
    return;
  }

  try {
    const respuesta = await fetchAutenticado(apiUsuarios + '?accion=eliminar_usuario', {
      method: 'POST',
      body: new URLSearchParams({ ci: usuario.ci })
    });
    const datos = await respuesta.json();
    if (!respuesta.ok || !datos.ok) throw new Error(datos.mensaje || 'No se pudo eliminar el usuario.');

    if (document.getElementById('ciUsuario').value === usuario.ci) cerrarEdicionUsuario();
    await Promise.all([cargarUsuariosRegistrados(), cargarPendientes()]);
    mensajeUsuariosRegistrados.textContent = datos.mensaje;
  } catch (error) {
    mensajeUsuariosRegistrados.textContent = error.message;
  }
}

formUsuario.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  const datosUsuario = new URLSearchParams({
    ci: document.getElementById('ciUsuario').value,
    nombre: document.getElementById('nombreUsuario').value,
    apellido: document.getElementById('apellidoUsuario').value,
    email: document.getElementById('emailUsuario').value,
    rol: document.getElementById('rolUsuario').value
  });

  try {
    const respuesta = await fetchAutenticado(apiUsuarios + '?accion=modificar_usuario', {
      method: 'POST',
      body: datosUsuario
    });
    const datos = await respuesta.json();
    if (!respuesta.ok || !datos.ok) throw new Error(datos.mensaje || 'No se pudo modificar el usuario.');

    cerrarEdicionUsuario();
    await Promise.all([cargarUsuariosRegistrados(), cargarPendientes()]);
    mensajeUsuariosRegistrados.textContent = datos.mensaje;
  } catch (error) {
    mensajeUsuariosRegistrados.textContent = error.message;
  }
});

document.getElementById('cancelarUsuario').addEventListener('click', cerrarEdicionUsuario);
cargarUsuariosRegistrados();
