document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        // ✅ Cargar eventos desde tu API
        events: function(info, successCallback, failureCallback) {
            fetch('events')
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    let events = data.map(function(event) {
                        return {
                            id: event.id,
                            title: event.title,
                            start: new Date(event.start),
                            end: new Date(event.end),
                            className: [event.className],
                            extendedProps: {
                                description: event.extendedProps.description,
                                type: event.extendedProps.type,
                                appointment_id: event.extendedProps.appointment_id,
                                patient_name: event.extendedProps.patient_name,
                                doctor_name: event.extendedProps.doctor_name
                            }
                        };
                    });
                    successCallback(events);
                })
                .catch(function(error) {
                    console.error('Error al cargar los eventos:', error);
                    failureCallback(error);
                });
        },

        eventContent: function(info) {
            return {
                html: `
                <div style="overflow: hidden; font-size: 12px; position: relative; cursor: pointer; font-family: 'Inter', sans-serif;">
                    <div><strong>${info.event.title}</strong></div>
                    <div>${info.event.start.toLocaleTimeString(
                        "es-ES",
                        {
                            hour: "2-digit",
                            minute: "2-digit"
                        }
                    )}</div>
                </div>
                `
            };
        },

        eventMouseEnter: function(mouseEnterInfo) {
            let el = mouseEnterInfo.el;
            el.classList.add("relative");

            let newEl = document.createElement("div");
            let newElTitle = mouseEnterInfo.event.title;
            let newElType = mouseEnterInfo.event.extendedProps.type || 'N/A';
            let newElPatient = mouseEnterInfo.event.extendedProps.patient_name || 'N/A';
            newEl.innerHTML = `
                <div
                    class="fc-hoverable-event"
                    style="position: absolute; bottom: 100%; left: 0; width: 300px; height: auto; background-color: white; z-index: 50; border: 1px solid #e2e8f0; border-radius: 0.375rem; padding: 0.75rem; font-size: 14px; font-family: 'Inter', sans-serif; cursor: pointer;"
                >
                    <strong>${newElTitle}</strong>
                    <div>Type: ${newElType}</div>
                    <div>Patient: ${newElPatient}</div>
                </div>
            `;
            el.after(newEl);
        },

        eventMouseLeave: function() {
            let hoverEvent = document.querySelector(".fc-hoverable-event");
            if (hoverEvent) {
                hoverEvent.remove();
            }
        },

        dateClick: function(info) {
            // Abrir modal para crear nuevo evento
            openNewEventModal(info.dateStr);
        },

        eventClick: function(info) {
            // Abrir modal para editar evento
            openEditEventModal(info.event);
        },

        eventDrop: function(info) {
            // Mover evento
            updateEventPosition(info.event);
        },

        eventResize: function(info) {
            // Cambiar duración del evento
            updateEventPosition(info.event);
        }
    });
    calendar.render();

    // Cargar pacientes y doctores en los selects
    loadPatients();
    loadDoctors();

    // Funciones para abrir modales
    function openNewEventModal(date) {
        document.getElementById('form-event').reset();
        document.getElementById('modal-title').innerText = 'Agregar Evento';
        document.getElementById('btn-delete-event').style.display = 'none';
        document.getElementById('event-start').value = date;
        document.getElementById('event-end').value = date;
        document.getElementById('btn-save-event').onclick = function() {
            saveEvent();
        };
        var myModal = new bootstrap.Modal(document.getElementById('event-modal'));
        myModal.show();
    }

    function openEditEventModal(event) {
        document.getElementById('event-title').value = event.title;
        document.getElementById('event-category').value = event.classNames[0];
        document.getElementById('event-description').value = event.extendedProps.description || '';
        document.getElementById('event-type').value = event.extendedProps.type || 'other';
        document.getElementById('event-start').value = event.start.toISOString().slice(0, 16);
        document.getElementById('event-end').value = event.end.toISOString().slice(0, 16);

        // Seleccionar paciente y doctor si existen
        if (event.extendedProps.patient_name) {
            const patientSelect = document.getElementById('event-patient');
            for (let option of patientSelect.options) {
                if (option.text === event.extendedProps.patient_name) {
                    option.selected = true;
                    break;
                }
            }
        }

        if (event.extendedProps.doctor_name) {
            const doctorSelect = document.getElementById('event-doctor');
            for (let option of doctorSelect.options) {
                if (option.text === event.extendedProps.doctor_name) {
                    option.selected = true;
                    break;
                }
            }
        }

        document.getElementById('modal-title').innerText = 'Editar Evento';
        document.getElementById('btn-delete-event').style.display = 'block';
        document.getElementById('btn-delete-event').onclick = function() {
            deleteEvent(event.id);
        };
        document.getElementById('btn-save-event').onclick = function() {
            updateEvent(event.id);
        };
        var myModal = new bootstrap.Modal(document.getElementById('event-modal'));
        myModal.show();
    }

    // Funciones para cargar pacientes y doctores
    function loadPatients() {
        fetch('patients-list') // Ajusta esta ruta a tu API de pacientes
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('event-patient');
                select.innerHTML = '<option value="">Seleccionar Paciente</option>';
                data.forEach(patient => {
                    const option = document.createElement('option');
                    option.value = patient.id;
                    option.text = patient.name + ' ' + patient.lastname;
                    select.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading patients:', error));
    }

    function loadDoctors() {
        fetch('doctors-list') // Ajusta esta ruta a tu API de doctores
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('event-doctor');
                select.innerHTML = '<option value="">Seleccionar Doctor</option>';
                data.forEach(doctor => {
                    const option = document.createElement('option');
                    option.value = doctor.id;
                    option.text = doctor.name;
                    select.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading doctors:', error));
    }

    // Funciones para guardar/actualizar/eliminar eventos
    function saveEvent() {
        const title = document.getElementById('event-title').value;
        const description = document.getElementById('event-description').value;
        const type = document.getElementById('event-type').value;
        const category = document.getElementById('event-category').value;
        const patientId = document.getElementById('event-patient').value;
        const doctorId = document.getElementById('event-doctor').value;
        const start = document.getElementById('event-start').value;
        const end = document.getElementById('event-end').value;
        // ✅ Eliminamos appointmentId porque no lo usamos

        if (!title) {
            alert('El nombre del evento es obligatorio');
            return;
        }

        fetch('add-event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                title: title,
                description: description,
                type: type,
                category: category,
                patient_id: patientId || null,
                doctor_id: doctorId || null,
                start: start,
                end: end
                // ❌ Eliminamos appointment_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                calendar.refetchEvents();
                bootstrap.Modal.getInstance(document.getElementById('event-modal')).hide();
            } else {
                alert('Error al guardar el evento');
            }
        });
    }

    function updateEvent(id) {
        const title = document.getElementById('event-title').value;
        const description = document.getElementById('event-description').value;
        const type = document.getElementById('event-type').value;
        const category = document.getElementById('event-category').value;
        const patientId = document.getElementById('event-patient').value;
        const doctorId = document.getElementById('event-doctor').value;
        const start = document.getElementById('event-start').value;
        const end = document.getElementById('event-end').value;
        // ✅ Eliminamos appointmentId porque no lo usamos

        if (!title) {
            alert('El nombre del evento es obligatorio');
            return;
        }

        fetch(`update-event?id=${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                title: title,
                description: description,
                type: type,
                category: category,
                patient_id: patientId || null,
                doctor_id: doctorId || null,
                start: start,
                end: end
                // ❌ Eliminamos appointment_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                calendar.refetchEvents();
                bootstrap.Modal.getInstance(document.getElementById('event-modal')).hide();
            } else {
                alert('Error al actualizar el evento');
            }
        });
    }

    function updateEventPosition(event) {
        fetch(`update-event?id=${event.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                title: event.title,
                description: event.extendedProps.description || '',
                start: event.startStr,
                end: event.endStr,
                type: event.extendedProps.type || 'other',
                category: event.classNames[0]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                alert('Error al actualizar la posición del evento');
            }
        });
    }

    function deleteEvent(id) {
        if (confirm('¿Estás seguro de que quieres eliminar este evento?')) {
            fetch(`delete-event?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    calendar.refetchEvents();
                    bootstrap.Modal.getInstance(document.getElementById('event-modal')).hide();
                } else {
                    alert('Error al eliminar el evento');
                }
            });
        }
    }

    // Botón para crear nuevo evento
    document.getElementById('btn-new-event').addEventListener('click', function() {
        openNewEventModal(new Date().toISOString().slice(0, 16));
    });

    // ✅ Prevenir el submit tradicional del formulario
    document.getElementById('form-event').addEventListener('submit', function(e) {
        e.preventDefault(); // ✅ Prevenir el envío tradicional
    });
});