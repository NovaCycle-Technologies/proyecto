const API_USUARIOS = '../Api_Usuarios/vista.php';

async function llamarApiUsuarios(accion, datos) {
  const respuesta = await fetch(`${API_USUARIOS}?accion=${accion}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(datos)
  });
  const resultado = await respuesta.json();
  if (!respuesta.ok) throw new Error(resultado.mensaje || 'No fue posible completar la operación.');
  return resultado;
}

function mostrarMensaje(elemento, mensaje, esError = false) {
  elemento.textContent = mensaje;
  elemento.hidden = false;
  elemento.classList.toggle('error', esError);
}

const formularioRegistro = document.getElementById('formRegistro');
if (formularioRegistro) {
  const mensaje = document.getElementById('mensajeRegistro');
  formularioRegistro.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    try {
      const resultado = await llamarApiUsuarios('registrar', Object.fromEntries(new FormData(formularioRegistro)));
      formularioRegistro.reset();
      alert(resultado.mensaje);
      window.location.assign('index.html');
    } catch (error) {
      mostrarMensaje(mensaje, error.message, true);
    }
  });
}

const formularioLogin = document.getElementById('formLogin');
if (formularioLogin) {
  const mensaje = document.getElementById('mensajeLogin');
  formularioLogin.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    try {
      const resultado = await llamarApiUsuarios('iniciar_sesion', Object.fromEntries(new FormData(formularioLogin)));
      sessionStorage.setItem('usuarioNovaCycle', JSON.stringify(resultado.usuario));
      const destinos = {
        admin_municipal: 'admin.html',
        peon: 'cuadrilla.html',
        conductor: 'cuadrilla.html',
        operario: 'operario.html'
      };
      const rol = String(resultado.usuario.rol || '').trim().toLowerCase();
      window.location.assign(resultado.destino || destinos[rol] || 'index.html');
    } catch (error) {
      mostrarMensaje(mensaje, error.message, true);
    }
  });
}
