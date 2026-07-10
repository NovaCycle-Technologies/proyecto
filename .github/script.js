document.addEventListener('DOMContentLoaded', () => {
  const btnCrearCuenta = document.getElementById('btnCrearCuenta');
  const btnIniciarSesion = document.getElementById('btnIniciarSesion');
  const btnNavbarIniciarSesion = document.getElementById('btnNavbarIniciarSesion');
  const btnNavbarRegistro = document.getElementById('btnNavbarRegistro');

  const botones = [
    { elemento: btnCrearCuenta, mensaje: 'Crear cuenta' },
    { elemento: btnIniciarSesion, mensaje: 'Iniciar sesión' },
    { elemento: btnNavbarIniciarSesion, mensaje: 'Iniciar sesión desde navbar' },
    { elemento: btnNavbarRegistro, mensaje: 'Registrarse' }
  ];

  botones.forEach(({ elemento, mensaje }) => {
    if (elemento) {
      elemento.addEventListener('click', () => {
        console.log(`Hiciste clic en: ${mensaje}`);
      });
    }
  });
});