<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Chatbot IA Gemini - Diseño Uiverse</title>
  <style>
    /* Reseteo básico */
    * {
      box-sizing: border-box;
    }

    body {
      background: #121212;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #fff;
      margin: 20px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      height: 100vh;
    }

    /* Contenedor principal */
    .container_chat_bot {
      display: flex;
      flex-direction: column;
      max-width: 260px;
      width: 100%;
    }

    .container_chat_bot .container-chat-options {
      position: relative;
      display: flex;
      background: linear-gradient(
        to bottom right,
        #7e7e7e,
        #363636,
        #363636,
        #363636,
        #363636
      );
      border-radius: 16px;
      padding: 1.5px;
      overflow: hidden;
    }
    .container_chat_bot .container-chat-options::after {
      position: absolute;
      content: "";
      top: -10px;
      left: -10px;
      background: radial-gradient(
        ellipse at center,
        #ffffff,
        rgba(255, 255, 255, 0.3),
        rgba(255, 255, 255, 0.1),
        rgba(0, 0, 0, 0),
        rgba(0, 0, 0, 0),
        rgba(0, 0, 0, 0),
        rgba(0, 0, 0, 0)
      );
      width: 30px;
      height: 30px;
      filter: blur(1px);
      pointer-events: none;
      z-index: 0;
    }

    .container_chat_bot .container-chat-options .chat {
      display: flex;
      flex-direction: column;
      background-color: rgba(0, 0, 0, 0.5);
      border-radius: 15px;
      width: 100%;
      overflow: hidden;
      z-index: 1;
    }

    .container_chat_bot .container-chat-options .chat .chat-bot {
      position: relative;
      display: flex;
    }

    .container_chat_bot .chat .chat-bot textarea {
      background-color: transparent;
      border-radius: 16px;
      border: none;
      width: 100%;
      height: 50px;
      color: #ffffff;
      font-family: sans-serif;
      font-size: 12px;
      font-weight: 400;
      padding: 10px;
      resize: none;
      outline: none;
    }

    /* Scrollbar */
    .container_chat_bot .chat .chat-bot textarea::-webkit-scrollbar {
      width: 10px;
      height: 10px;
    }
    .container_chat_bot .chat .chat-bot textarea::-webkit-scrollbar-track {
      background: transparent;
    }
    .container_chat_bot .chat .chat-bot textarea::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 5px;
    }
    .container_chat_bot .chat .chat-bot textarea::-webkit-scrollbar-thumb:hover {
      background: #555;
      cursor: pointer;
    }

    .container_chat_bot .chat .chat-bot textarea::placeholder {
      color: #f3f6fd;
      transition: all 0.3s ease;
    }
    .container_chat_bot .chat .chat-bot textarea:focus::placeholder {
      color: #363636;
    }

    .container_chat_bot .chat .options {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      padding: 10px;
    }

    .container_chat_bot .chat .options .btns-add {
      display: flex;
      gap: 8px;
    }
    .container_chat_bot .chat .options .btns-add button {
      display: flex;
      color: rgba(255, 255, 255, 0.1);
      background-color: transparent;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      padding: 0;
    }
    .container_chat_bot .chat .options .btns-add button:hover {
      transform: translateY(-5px);
      color: #ffffff;
    }
    .container_chat_bot .chat .options .btns-add button svg {
      pointer-events: none;
    }

    .container_chat_bot .chat .options .btn-submit {
      display: flex;
      padding: 2px;
      background-image: linear-gradient(to top, #292929, #555555, #292929);
      border-radius: 10px;
      box-shadow: inset 0 6px 2px -4px rgba(255, 255, 255, 0.5);
      cursor: pointer;
      border: none;
      outline: none;
      transition: all 0.15s ease;
    }
    .container_chat_bot .chat .options .btn-submit i {
      width: 30px;
      height: 30px;
      padding: 6px;
      background: rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      backdrop-filter: blur(3px);
      color: #8b8b8b;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .container_chat_bot .chat .options .btn-submit svg {
      transition: all 0.3s ease;
      width: 18px;
      height: 18px;
    }
    .container_chat_bot .chat .options .btn-submit:hover svg,
    .container_chat_bot .chat .options .btn-submit:focus svg {
      color: #f3f6fd;
      filter: drop-shadow(0 0 5px #ffffff);
    }
    .container_chat_bot .chat .options .btn-submit:focus svg {
      transform: scale(1.2) rotate(45deg) translateX(-2px) translateY(1px);
    }
    .container_chat_bot .chat .options .btn-submit:active {
      transform: scale(0.92);
    }

    .container_chat_bot .tags {
      padding: 14px 0;
      display: flex;
      color: #ffffff;
      font-size: 10px;
      gap: 4px;
    }
    .container_chat_bot .tags span {
      padding: 4px 8px;
      background-color: #1b1b1b;
      border: 1.5px solid #363636;
      border-radius: 10px;
      cursor: pointer;
      user-select: none;
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .container_chat_bot .tags span:hover {
      background-color: #363636;
      border-color: #7e7e7e;
    }
  </style>
</head>
<body>

  <div class="container_chat_bot">
    <div class="container-chat-options">
      <div class="chat">
        <div class="chat-bot">
          <textarea
            id="chat_bot"
            name="chat_bot"
            placeholder="Imagine Something...✦˚"
          ></textarea>
        </div>
        <div class="options">
          <div class="btns-add">
            <button title="Add link">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
              >
                <path
                  fill="none"
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 8v8a5 5 0 1 0 10 0V6.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 0 4 0V8"
                ></path>
              </svg>
            </button>
            <button title="Add grid">
              <svg
                viewBox="0 0 24 24"
                height="20"
                width="20"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M4 5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1zm0 10a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1zm10-10a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1zm0 10a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1z"
                  fill="none"
                  stroke="currentColor"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  stroke-width="2"
                ></path>
              </svg>
            </button>
            <button title="Add folder">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M3 7h5l2 3h11v10H3z"></path>
              </svg>
            </button>
            <button title="Add image">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <rect width="18" height="14" x="3" y="5" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <path d="M21 15l-5-5L5 21"></path>
              </svg>
            </button>
          </div>
          <button class="btn-submit" title="Send message">
            <i>
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
            </i>
          </button>
        </div>
      </div>
    </div>

    <div class="tags">
      <span>Color</span>
      <span>Design</span>
      <span>Geometry</span>
      <span>Abstract</span>
    </div>
  </div>

</body>
</html>
