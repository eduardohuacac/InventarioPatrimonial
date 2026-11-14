// Comportamiento mínimo de la interfaz (sin librerías externas).
document.addEventListener('DOMContentLoaded', function () {
  var boton = document.querySelector('[data-menu]');
  var lateral = document.querySelector('.lateral');
  if (boton && lateral) {
    boton.addEventListener('click', function () {
      var abierto = lateral.classList.toggle('abierto');
      boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
  }

  // Confirmación antes de acciones que no se pueden deshacer.
  document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirmar'))) { e.preventDefault(); }
    });
  });

  // Verificación física: el estado solo aplica si el bien fue encontrado.
  var encontrado = document.querySelectorAll('input[name="encontrado"]');
  var bloque = document.getElementById('bloque-encontrado');
  function sincronizar() {
    var marcado = document.querySelector('input[name="encontrado"]:checked');
    if (bloque && marcado) { bloque.hidden = marcado.value !== '1'; }
  }
  encontrado.forEach(function (r) { r.addEventListener('change', sincronizar); });
  sincronizar();
});
