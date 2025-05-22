<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario Interactivo</title>
    <link rel="stylesheet" href="calendar.css">
</head>
<body>
  <center>
    <div class="container10">
        <!-- From Uiverse.io by vinodjangid07 --> 
         <a href="index.php" style="text-decoration:none">
            <style>
                /* From Uiverse.io by vinodjangid07 */ 
.Btn {
  position: relative;
  width: 150px;
  height: 55px;
  border-radius: 45px;
  border: none;
  background-color: rgb(255, 95, 95);
  color: white;
  box-shadow: 0px 10px 10px rgb(253, 187, 187) inset,
  0px 5px 10px rgba(5, 5, 5, 0.21),
  0px -10px 10px rgb(255, 54, 54) inset;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.Btn::before {
  width: 70%;
  height: 2px;
  position: absolute;
  background-color: rgba(250, 250, 250, 0.678);
  content: "";
  filter: blur(1px);
  top: 7px;
  border-radius: 50%;
}

.Btn::after {
  width: 70%;
  height: 2px;
  position: absolute;
  background-color: rgba(250, 250, 250, 0.137);
  content: "";
  filter: blur(1px);
  bottom: 7px;
  border-radius: 50%;
}

.Btn:hover {
  animation: jello-horizontal 0.9s both;
}

@keyframes jello-horizontal {
  0% {
    transform: scale3d(1, 1, 1);
  }

  30% {
    transform: scale3d(1.25, 0.75, 1);
  }

  40% {
    transform: scale3d(0.75, 1.25, 1);
  }

  50% {
    transform: scale3d(1.15, 0.85, 1);
  }

  65% {
    transform: scale3d(0.95, 1.05, 1);
  }

  75% {
    transform: scale3d(1.05, 0.95, 1);
  }

  100% {
    transform: scale3d(1, 1, 1);
  }
}
.contenido-total{
  width: 100%;
  height: 100%;
}

            </style>
<button class="Btn">
  Volver
</button>
</a>


        <header>
            <h1>Calendario Interactivo</h1>
            <div class="month-navigation">
                <button id="prev-month">< Anterior</button>
                <h2 id="current-month-year"></h2>
                <button id="next-month">Siguiente ></button>
            </div>
        </header>

        <div class="calendar-grid">
            <div class="weekday">Dom</div>
            <div class="weekday">Lun</div>
            <div class="weekday">Mar</div>
            <div class="weekday">Mié</div>
            <div class="weekday">Jue</div>
            <div class="weekday">Vie</div>
            <div class="weekday">Sáb</div>
            <div id="calendar-days" class="days-grid">
                <!-- Los días se generarán aquí -->
            </div>
        </div>

        <div class="info-panel">
            <h3>Información Anual</h3>
            <p id="days-passed-year"></p>
            
            <h3>Próximos Eventos Importantes</h3>
            <ul id="upcoming-events-list">
                <!-- Lista de eventos -->
            </ul>
        </div>

        <div class="event-form-container">
            <h3>Agregar Evento Personalizado</h3>
            <form id="add-event-form">
                <label for="event-date">Fecha:</label>
                <input type="date" id="event-date" required>
                
                <label for="event-name">Nombre del Evento:</label>
                <input type="text" id="event-name" placeholder="Ej: Cumpleaños de Ana" required>
                
                <button type="submit">Agregar Evento</button>
            </form>
        </div>

        <div id="notifications-area">
            <!-- Las notificaciones aparecerán aquí -->
        </div>
    
</div>
</center>
    <script src="calendar.js"></script>
</body>
</html>