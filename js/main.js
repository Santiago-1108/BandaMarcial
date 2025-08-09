// JavaScript para el sistema de banda marcial

// Función para confirmar eliminaciones
function confirmarEliminacion(mensaje = "¿Está seguro de que desea eliminar este elemento?") {
  return confirm(mensaje)
}

// Función para mostrar notificaciones dinámicas
function mostrarNotificacion(mensaje, tipo = "success") {
  // No mostrar notificaciones de auto-guardado
  if (mensaje.includes("Borrador guardado") || mensaje.includes("automáticamente")) {
    return
  }

  // Crear elemento de notificación
  const notificacion = document.createElement("div")
  notificacion.className = `alert alert-${tipo}`
  notificacion.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    min-width: 300px;
    animation: slideIn 0.3s ease-out;
  `
  notificacion.innerHTML = `
    ${mensaje}
    <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
  `

  document.body.appendChild(notificacion)

  // Auto-remover después de 5 segundos
  setTimeout(() => {
    if (notificacion.parentElement) {
      notificacion.style.animation = "slideOut 0.3s ease-in"
      setTimeout(() => notificacion.remove(), 300)
    }
  }, 5000)
}

// Función para envío de formularios con AJAX (no se usa actualmente para redirecciones PHP)
function enviarFormularioAjax(formId, successCallback) {
  const form = document.getElementById(formId)
  if (!form) return

  form.addEventListener("submit", (e) => {
    e.preventDefault()

    const formData = new FormData(form)
    const submitBtn = form.querySelector('button[type="submit"]')
    const originalText = submitBtn.textContent

    // Mostrar estado de carga
    submitBtn.disabled = true
    submitBtn.textContent = "Procesando..."

    fetch(form.action || window.location.href, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((data) => {
        // Si la respuesta contiene una redirección, seguirla
        if (data.includes("Location:") || data.includes("header(")) {
          window.location.reload()
        } else {
          if (successCallback) successCallback(data)
          mostrarNotificacion("Operación completada exitosamente")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        mostrarNotificacion("Error al procesar la solicitud", "error")
      })
      .finally(() => {
        submitBtn.disabled = false
        submitBtn.textContent = originalText
      })
  })
}

// Función para búsqueda en tiempo real
function busquedaTiempoReal(inputId, tableId, delay = 300) {
  const input = document.getElementById(inputId)
  const table = document.getElementById(tableId)

  if (!input || !table) return

  let timeout

  input.addEventListener("input", function () {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      const filter = this.value.toLowerCase()
      const rows = table.getElementsByTagName("tr")

      let visibleRowCount = 0 // Contador de filas visibles

      for (let i = 1; i < rows.length; i++) {
        const row = rows[i]
        // Ignorar la fila de "no-results-message" si existe
        if (row.classList.contains("no-results-message")) {
          row.style.display = "none"
          continue
        }

        const cells = row.getElementsByTagName("td")
        let found = false

        for (let j = 0; j < cells.length; j++) {
          const cell = cells[j]
          if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
            found = true
            break
          }
        }

        if (found) {
          row.style.display = ""
          visibleRowCount++
        } else {
          row.style.display = "none"
        }
      }

      // Mostrar mensaje si no hay resultados visibles después del filtro
      if (visibleRowCount === 0) {
        mostrarMensajeTabla(table, "No se encontraron resultados para la búsqueda.")
      } else {
        // Asegurarse de que el mensaje de "no resultados" esté oculto si hay filas
        const existingMsg = table.querySelector(".no-results-message")
        if (existingMsg) {
          existingMsg.style.display = "none"
        }
      }
    }, delay)
  })
}

// Función para mostrar mensaje en tabla vacía
function mostrarMensajeTabla(table, mensaje) {
  const tbody = table.querySelector("tbody")
  const existingMsg = tbody.querySelector(".no-results-message")

  if (existingMsg) {
    existingMsg.remove()
  }

  const row = document.createElement("tr")
  row.className = "no-results-message"
  row.innerHTML = `<td colspan="100%" class="no-results-cell">${mensaje}</td>`
  tbody.appendChild(row)
}

// Función para validación de formularios en tiempo real
function validarFormulario(formId) {
  const form = document.getElementById(formId)
  if (!form) return

  const inputs = form.querySelectorAll("input[required], select[required], textarea[required]")

  inputs.forEach((input) => {
    input.addEventListener("blur", function () {
      validarCampo(this)
    })

    input.addEventListener("input", function () {
      if (this.classList.contains("error")) {
        validarCampo(this)
      }
    })
  })

  // Add submit event listener to disable button and show loading
  form.addEventListener('submit', function() {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> Procesando...'; // Add spinner
    }
  });
}

function validarCampo(campo) {
  const errorMsg = campo.parentElement.querySelector(".error-message")

  if (errorMsg) {
    errorMsg.remove()
  }

  campo.classList.remove("error")

  if (!campo.value.trim() && campo.required) {
    mostrarErrorCampo(campo, "Este campo es obligatorio")
    return false
  }

  // Validaciones específicas
  if (campo.type === "email" && campo.value && !isValidEmail(campo.value)) {
    mostrarErrorCampo(campo, "Ingrese un email válido")
    return false
  }

  return true
}

function mostrarErrorCampo(campo, mensaje) {
  campo.classList.add("error")

  const errorDiv = document.createElement("div")
  errorDiv.className = "error-message"
  errorDiv.style.cssText = "color: var(--color-danger-base); font-size: 12px; margin-top: 5px;"
  errorDiv.textContent = mensaje
  campo.parentElement.appendChild(errorDiv)
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

// Función para mostrar/ocultar campos según el tipo de uniforme y cargar tallas
function toggleTallaField() {
  const tipoSelect = document.getElementById("tipo")
  const tallaGroup = document.getElementById("talla-group")
  const tallaSelect = document.getElementById("talla")

  if (tipoSelect && tallaGroup && tallaSelect) {
    const tipo = tipoSelect.value
    tallaSelect.innerHTML = '<option value="">Seleccionar talla</option>' // Limpiar opciones

    if (tipo === "Chaleco") {
      tallaGroup.style.display = "block"
      tallaSelect.required = true
      tallaGroup.style.animation = "fadeIn 0.3s ease-in"
      // Opciones de talla para chaleco
      const tallasChaleco = ["XS", "S", "M", "L", "XL", "XXL"]
      tallasChaleco.forEach((talla) => {
        const option = document.createElement("option")
        option.value = talla
        option.textContent = talla
        tallaSelect.appendChild(option)
      })
    } else if (tipo === "Boina") {
      tallaGroup.style.display = "block"
      tallaSelect.required = true
      tallaGroup.style.animation = "fadeIn 0.3s ease-in"
      // Opciones de talla para boina (1-10)
      for (let i = 1; i <= 10; i++) {
        const option = document.createElement("option")
        option.value = i
        option.textContent = i
        tallaSelect.appendChild(option)
      }
    } else {
      tallaGroup.style.display = "none"
      tallaSelect.required = false
      tallaSelect.value = ""
    }

    // Si estamos en la página de edición, preseleccionar la talla existente
    if (window.location.pathname.includes("uniformes/editar.php")) {
      const currentTalla = tallaSelect.dataset.currentTalla
      if (currentTalla) {
        tallaSelect.value = currentTalla
      }
    }
  }
}

// Función para validar fechas en préstamos
function validarFechas() {
  const fechaPrestamo = document.getElementById("fecha_prestamo")
  const fechaDevolucion = document.getElementById("fecha_devolucion_esperada")

  if (fechaPrestamo && fechaDevolucion) {
    fechaPrestamo.addEventListener("change", function () {
      fechaDevolucion.min = this.value
      if (fechaDevolucion.value && fechaDevolucion.value < this.value) {
        fechaDevolucion.value = this.value
      }
    })
  }
}

let itemCounter = 0 // Contador global para items dinámicos

// Función para agregar items al préstamo dinámicamente
function agregarItem() {
  const container = document.getElementById("items-container")
  itemCounter++ // Incrementar para el nuevo item

  const itemDiv = document.createElement("div")
  itemDiv.className = "form-row item-row"
  itemDiv.dataset.itemIndex = itemCounter // Asignar el índice al nuevo div
  itemDiv.innerHTML = `
        <div class="form-group">
            <label>Tipo de Item:</label>
            <select name="tipo_item[]" class="form-control" onchange="cargarItems(this, ${itemCounter})" required>
                <option value="">Seleccionar tipo</option>
                <option value="instrumento">Instrumento</option>
                <option value="uniforme">Uniforme</option>
                <option value="accesorio">Accesorio</option>
            </select>
        </div>
        <div class="form-group">
            <label>Item:</label>
            <select name="item_id[]" class="form-control" id="item_select_${itemCounter}" required>
                <option value="">Seleccionar item</option>
            </select>
        </div>
        <div class="form-group" style="display: flex; align-items: end;">
            <button type="button" class="btn btn-danger" onclick="eliminarItem(this)">Eliminar</button>
        </div>
    `
  container.appendChild(itemDiv)
}

// Función para eliminar item del préstamo
function eliminarItem(button) {
  const itemRow = button.closest(".item-row")
  itemRow.remove()
}

// Función para cargar items disponibles según el tipo
function cargarItems(selectTipo, index) {
  const itemSelect = document.getElementById(`item_select_${index}`)
  const tipo = selectTipo.value

  // Resetear campos
  itemSelect.innerHTML = '<option value="">Seleccionar item</option>'

  if (!tipo) {
    return
  }

  // Mostrar estado de carga
  itemSelect.innerHTML = '<option value="">Cargando...</option>'
  itemSelect.disabled = true; // Disable select during loading

  // Realizar petición AJAX para obtener items disponibles
  fetch(`get_items_disponibles.php?tipo=${tipo}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Error en la respuesta del servidor")
      }
      return response.json()
    })
    .then((data) => {
      itemSelect.innerHTML = '<option value="">Seleccionar item</option>'

      if (data.error) {
        console.error("Error del servidor:", data.error)
        itemSelect.innerHTML = '<option value="">Error al cargar items</option>'
        return
      }

      if (data.length === 0) {
        itemSelect.innerHTML = '<option value="">No hay items disponibles</option>'
        return
      }

      data.forEach((item) => {
        const option = document.createElement("option")
        option.value = item.id
        if (tipo === "instrumento") {
          option.textContent = `${item.nombre} - ${item.codigo_serie}`
        } else if (tipo === "uniforme") {
          // Para uniformes, mostrar tipo, talla y código de serie
          option.textContent = `${item.tipo} Talla ${item.talla} - ${item.codigo_serie}`
        } else if (tipo === "accesorio") {
          // Para accesorios, mostrar nombre y código de serie
          option.textContent = `${item.nombre} - ${item.codigo_serie}`
        }
        itemSelect.appendChild(option)
      })
    })
    .catch((error) => {
      console.error("Error al cargar items:", error)
      itemSelect.innerHTML = '<option value="">Error al cargar items</option>'
      mostrarNotificacion("Error al cargar items disponibles", "error")
    })
    .finally(() => {
      itemSelect.disabled = false; // Re-enable select after loading
    });
}

// Función para actualizar estado de préstamo automáticamente
function actualizarEstadoPrestamos() {
  fetch("actualizar_estados.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.updated > 0) {
        console.log(`${data.updated} préstamos actualizados a vencidos`)
      }
    })
    .catch((error) => {
      console.error("Error al actualizar estados:", error);
    })
}

// NUEVO: Función para inicializar colapsables
function setupCollapsibles() {
  const toggles = document.querySelectorAll(".collapsible-toggle")

  toggles.forEach((toggle) => {
    const contentId = toggle.dataset.target
    const content = document.getElementById(contentId)
    const icon = toggle.querySelector(".toggle-icon")

    // Iniciar todos colapsados por defecto en el dashboard para ahorrar espacio
    if (content) {
      content.style.maxHeight = "0"
      content.classList.remove("expanded")
      if (icon) icon.textContent = "▼" // Down arrow for collapsed
    }

    toggle.addEventListener("click", () => {
      if (content.style.maxHeight === "0px" || content.style.maxHeight === "") {
        content.style.maxHeight = content.scrollHeight + "px" // Expand
        content.classList.add("expanded")
        if (icon) icon.textContent = "▲" // Up arrow for expanded
      } else {
        content.style.maxHeight = "0" // Collapse
        content.classList.remove("expanded")
        if (icon) icon.textContent = "▼" // Down arrow for collapsed
      }
    })
  })
}

// Agregar estilos CSS dinámicamente
function agregarEstilosDinamicos() {
  const style = document.createElement("style")
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 0; }
      to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-15px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .form-control.error {
      border-color: var(--color-danger-base) !important;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
    }
    
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }
    
    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .spinner {
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top: 2px solid #fff;
      width: 1em;
      height: 1em;
      -webkit-animation: spin 1s linear infinite;
      animation: spin 1s linear infinite;
      display: inline-block;
      vertical-align: middle;
      margin-right: 0.5em;
    }
  `
  document.head.appendChild(style)
}

// Inicializar todas las funciones cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  // Agregar estilos dinámicos
  agregarEstilosDinamicos()

  // Configurar búsqueda en tiempo real
  busquedaTiempoReal("buscar", "tabla-datos")

  // Validar fechas en formularios de préstamo
  validarFechas()

  // Configurar campo de talla para uniformes
  const tipoSelect = document.getElementById("tipo")
  if (tipoSelect) {
    // Al cargar la página, si ya hay un tipo seleccionado (ej. en editar.php)
    // o si es la página de crear, inicializar las opciones de talla.
    if (tipoSelect.value) {
      toggleTallaField()
    }
    tipoSelect.addEventListener("change", toggleTallaField)
  }

  // Validar formularios
  const forms = [
    "form-instrumento",
    "form-uniforme",
    "form-estudiante",
    "form-prestamo",
    "form-accesorio",
    "form-repuesto",
    "form-salida-repuesto",
  ]
  forms.forEach((formId) => {
    validarFormulario(formId)
  })

  // Actualizar estados de préstamos
  if (window.location.pathname.includes("prestamos")) {
    actualizarEstadoPrestamos()
    setInterval(actualizarEstadoPrestamos, 300000)
  }

  // Configurar tooltips para estados
  const estados = document.querySelectorAll(".estado")
  estados.forEach((estado) => {
    estado.title = estado.textContent
  })

  // Manejar mensajes de URL y mostrar notificaciones
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get("success")) {
    mostrarNotificacion(urlParams.get("success"), "success")
  }
  if (urlParams.get("error")) {
    mostrarNotificacion(urlParams.get("error"), "error")
  }

  // NUEVO: Configurar colapsables si estamos en el dashboard
  if (window.location.pathname.includes("index.php") || window.location.pathname === "/") {
    setupCollapsibles()
  }
})

// Función para imprimir reportes
function imprimirReporte() {
  window.print()
}

// Función para exportar datos (básica)
function exportarDatos(formato) {
  if (formato === "csv") {
    alert("Funcionalidad de exportación CSV en desarrollo")
  } else if (formato === "pdf") {
    window.print()
  }
}
