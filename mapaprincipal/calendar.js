document.addEventListener('DOMContentLoaded', () => {
    const currentMonthYearElement = document.getElementById('current-month-year');
    const calendarDaysElement = document.getElementById('calendar-days');
    const prevMonthButton = document.getElementById('prev-month');
    const nextMonthButton = document.getElementById('next-month');
    const daysPassedYearElement = document.getElementById('days-passed-year');
    const upcomingEventsListElement = document.getElementById('upcoming-events-list');
    const addEventForm = document.getElementById('add-event-form');
    const eventDateInput = document.getElementById('event-date');
    const notificationsArea = document.getElementById('notifications-area');

    let currentDate = new Date();
    let events = []; // { date: "YYYY-MM-DD", name: "Event Name", type: "holiday" | "custom" }

    const PERU_HOLIDAYS = [
        { date: "01-01", name: "Año Nuevo", type: "holiday" },
        // Jueves Santo y Viernes Santo varían, se calculan
        { date: "05-01", name: "Día del Trabajo", type: "holiday" },
        { date: "06-29", name: "San Pedro y San Pablo", type: "holiday" },
        { date: "07-28", name: "Fiestas Patrias", type: "holiday" },
        { date: "07-29", name: "Fiestas Patrias", type: "holiday" },
        { date: "08-30", name: "Santa Rosa de Lima", type: "holiday" },
        { date: "10-08", name: "Combate de Angamos", type: "holiday" },
        { date: "11-01", name: "Día de Todos los Santos", type: "holiday" },
        { date: "12-08", name: "Inmaculada Concepción", type: "holiday" },
        { date: "12-09", name: "Batalla de Ayacucho", type: "holiday" }, // Nuevo feriado
        { date: "12-25", name: "Navidad", type: "holiday" }
    ];

    function getEaster(year) { // Algoritmo de Butcher para Domingo de Pascua Gregoriano
        const a = year % 19;
        const b = Math.floor(year / 100);
        const c = year % 100;
        const d = Math.floor(b / 4);
        const e = b % 4;
        const f = Math.floor((b + 8) / 25);
        const g = Math.floor((b - f + 1) / 3);
        const h = (19 * a + b - d - g + 15) % 30;
        const i = Math.floor(c / 4);
        const k = c % 4;
        const l = (32 + 2 * e + 2 * i - h - k) % 7;
        const m = Math.floor((a + 11 * h + 22 * l) / 451);
        const month = Math.floor((h + l - 7 * m + 114) / 31);
        const day = ((h + l - 7 * m + 114) % 31) + 1;
        return new Date(year, month - 1, day);
    }

    function loadInitialEvents(year) {
        events = []; // Limpiar eventos existentes antes de cargar nuevos para el año
        
        // Cargar feriados fijos de Perú
        PERU_HOLIDAYS.forEach(holiday => {
            events.push({
                date: `${year}-${holiday.date}`,
                name: holiday.name,
                type: holiday.type
            });
        });

        // Calcular y agregar Jueves y Viernes Santo
        const easterSunday = getEaster(year);
        const holyThursday = new Date(easterSunday);
        holyThursday.setDate(easterSunday.getDate() - 3);
        const goodFriday = new Date(easterSunday);
        goodFriday.setDate(easterSunday.getDate() - 2);

        events.push({
            date: formatDate(holyThursday),
            name: "Jueves Santo",
            type: "holiday"
        });
        events.push({
            date: formatDate(goodFriday),
            name: "Viernes Santo",
            type: "holiday"
        });
        
        // Cargar eventos personalizados desde localStorage
        const storedCustomEvents = JSON.parse(localStorage.getItem('customCalendarEvents') || '[]');
        storedCustomEvents.forEach(event => {
            // Solo agregar si no existe ya un evento con el mismo nombre y fecha (evita duplicados al cambiar año)
            if (!events.find(e => e.date === event.date && e.name === event.name)) {
                 events.push(event);
            }
        });
        events.sort((a, b) => new Date(a.date) - new Date(b.date));
    }
    
    function saveCustomEvents() {
        const customEvents = events.filter(event => event.type === 'custom');
        localStorage.setItem('customCalendarEvents', JSON.stringify(customEvents));
    }

    function renderCalendar() {
        calendarDaysElement.innerHTML = '';
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth(); // 0-11

        currentMonthYearElement.textContent = `${currentDate.toLocaleString('es-ES', { month: 'long' })} ${year}`;
        
        // Recargar eventos si el año ha cambiado para recalcular feriados dinámicos como Pascua
        if (events.length === 0 || !events.find(e => e.date.startsWith(year))) {
            loadInitialEvents(year);
        }

        const firstDayOfMonth = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const startDayOfWeek = firstDayOfMonth.getDay(); // 0 (Domingo) - 6 (Sábado)

        // Celdas vacías para los días antes del inicio del mes
        for (let i = 0; i < startDayOfWeek; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.classList.add('day-cell', 'empty');
            calendarDaysElement.appendChild(emptyCell);
        }

        // Celdas para cada día del mes
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div');
            dayCell.classList.add('day-cell');
            const dayNumberSpan = document.createElement('span');
            dayNumberSpan.classList.add('day-number');
            dayNumberSpan.textContent = day;
            dayCell.appendChild(dayNumberSpan);

            const cellDate = new Date(year, month, day);
            const cellDateString = formatDate(cellDate);

            if (isToday(cellDate)) {
                dayCell.classList.add('current-day');
            }

            const dayEvents = events.filter(event => event.date === cellDateString);
            dayEvents.forEach(event => {
                dayCell.classList.add(event.type === 'holiday' ? 'event-holiday' : 'event-custom');
                const eventMarker = document.createElement('div');
                eventMarker.classList.add('event-marker', event.type === 'holiday' ? 'event-holiday' : 'event-custom');
                eventMarker.textContent = event.name;
                dayCell.appendChild(eventMarker);
            });
            
            dayCell.addEventListener('click', () => handleDayClick(cellDateString));
            calendarDaysElement.appendChild(dayCell);
        }
        updateInfoPanel();
    }
    
    function handleDayClick(dateString) {
        // Pre-llenar el formulario de agregar evento con la fecha clickeada
        eventDateInput.value = dateString;
        document.getElementById('event-name').focus();

        const dayEvents = events.filter(event => event.date === dateString);
        if (dayEvents.length > 0) {
            let eventDetails = `Eventos para ${new Date(dateString + 'T00:00:00').toLocaleDateString('es-ES', {day: 'numeric', month: 'long'})}:\n`;
            dayEvents.forEach(event => {
                eventDetails += `- ${event.name} (${event.type})\n`;
            });
            // Podrías usar un modal más elegante, pero alert es simple
            // alert(eventDetails); 
        }
    }

    function isToday(date) {
        const today = new Date();
        return date.getDate() === today.getDate() &&
               date.getMonth() === today.getMonth() &&
               date.getFullYear() === today.getFullYear();
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function updateInfoPanel() {
        // Días pasados desde el inicio del año
        const today = new Date();
        const startOfYear = new Date(today.getFullYear(), 0, 1);
        const diffTime = Math.abs(today - startOfYear);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        daysPassedYearElement.textContent = `Han pasado ${diffDays} días desde el inicio del año ${today.getFullYear()}.`;

        // Próximos eventos y notificaciones
        upcomingEventsListElement.innerHTML = '';
        notificationsArea.innerHTML = ''; // Limpiar notificaciones antiguas
        
        const upcoming = events.filter(event => new Date(event.date + "T00:00:00") >= new Date(formatDate(new Date()) + "T00:00:00"))
                               .sort((a,b) => new Date(a.date) - new Date(b.date))
                               .slice(0, 5); // Mostrar los próximos 5

        upcoming.forEach(event => {
            const eventDate = new Date(event.date + "T00:00:00"); // Asegurar que se compara solo la fecha
            const todayDateOnly = new Date(formatDate(new Date()) + "T00:00:00");
            
            const timeDiffToEvent = eventDate.getTime() - todayDateOnly.getTime();
            const daysRemaining = Math.ceil(timeDiffToEvent / (1000 * 60 * 60 * 24));

            const listItem = document.createElement('li');
            let remainingText = "";
            if (daysRemaining === 0) {
                remainingText = "(Hoy)";
            } else if (daysRemaining === 1) {
                remainingText = "(Mañana)";
            } else if (daysRemaining > 1) {
                remainingText = `(Faltan ${daysRemaining} días)`;
            }
            
            listItem.textContent = `${event.name} - ${eventDate.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })} ${remainingText}`;
            if (event.type === 'holiday') listItem.style.color = '#e74c3c';
            if (event.type === 'custom') listItem.style.color = '#2ecc71';
            upcomingEventsListElement.appendChild(listItem);

            // Notificaciones 3 días antes
            if (daysRemaining > 0 && daysRemaining <= 3) {
                showNotification(`¡Atención! ${event.name} es en ${daysRemaining} día(s).`, 'warning');
            }
        });
        if (upcoming.length === 0) {
            upcomingEventsListElement.innerHTML = '<li>No hay eventos próximos en la lista.</li>';
        }
    }

    function showNotification(message, type = 'info') { // type can be 'info', 'warning'
        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        notification.textContent = message;
        notificationsArea.appendChild(notification);

        // Auto-eliminar notificación después de 7 segundos
        setTimeout(() => {
            notification.remove();
        }, 7000);
    }

    addEventForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const eventName = document.getElementById('event-name').value.trim();
        const eventDate = eventDateInput.value;

        if (eventName && eventDate) {
            // Verificar si ya existe un evento igual para evitar duplicados
            const existingEvent = events.find(ev => ev.date === eventDate && ev.name === eventName && ev.type === 'custom');
            if (existingEvent) {
                showNotification(`El evento "${eventName}" en esta fecha ya existe.`, 'warning');
                return;
            }

            events.push({ date: eventDate, name: eventName, type: 'custom' });
            events.sort((a, b) => new Date(a.date) - new Date(b.date));
            saveCustomEvents();
            renderCalendar(); // Re-render para mostrar el nuevo evento
            addEventForm.reset();
            showNotification(`Evento "${eventName}" agregado para el ${new Date(eventDate+'T00:00:00').toLocaleDateString()}.`, 'info');
        } else {
            showNotification('Por favor, completa la fecha y el nombre del evento.', 'warning');
        }
    });

    prevMonthButton.addEventListener('click', () => {
        const currentYear = currentDate.getFullYear();
        currentDate.setMonth(currentDate.getMonth() - 1);
        // Si el mes cambia de enero a diciembre del año anterior, recargar eventos para ese año
        if (currentDate.getFullYear() < currentYear) {
            loadInitialEvents(currentDate.getFullYear());
        }
        renderCalendar();
    });

    nextMonthButton.addEventListener('click', () => {
        const currentYear = currentDate.getFullYear();
        currentDate.setMonth(currentDate.getMonth() + 1);
        // Si el mes cambia de diciembre a enero del siguiente año, recargar eventos
        if (currentDate.getFullYear() > currentYear) {
            loadInitialEvents(currentDate.getFullYear());
        }
        renderCalendar();
    });
    
    // Inicialización
    loadInitialEvents(currentDate.getFullYear()); // Carga inicial de eventos para el año actual
    renderCalendar();
});