Multi-AI Terminal

A unified, secure, multi-platform AI workspace that connects multiple AI models through one interface.

Multi-AI Terminal is designed to provide a single place to interact with different AI providers while keeping authentication, conversations, API keys, usage, and security under one system.

Features

* 🤖 Support for multiple AI providers and models
* 💬 Unified AI chat interface
* 🔐 User authentication and session management
* 🗂️ Conversation and message history
* 🔑 Secure API-key management
* 🛡️ Security-focused architecture
* 📊 Usage tracking and limits
* 🌐 Web application
* 🖥️ Windows desktop application
* 📱 Android application
* ⚙️ Automated builds with GitHub Actions

Project Structure

multi-ai-terminal/
├── app/
├── api/
├── config/
├── database/
├── public/
├── desktop/
├── android/
├── .github/
│   └── workflows/
├── .gitignore
├── LICENSE
└── README.md

Architecture

                    Multi-AI Terminal
                           │
              ┌────────────┼────────────┐
              │            │            │
           Website       Windows      Android
              │            │            │
              └────────────┼────────────┘
                           │
                      Backend API
                           │
                  AI Provider Layer
                           │
            ┌──────────────┼──────────────┐
            │              │              │
         Provider A     Provider B     Provider C

The clients communicate with the backend API rather than directly exposing provider credentials.

Technology

Backend

* PHP 8.4+
* MySQL / MariaDB
* PDO
* REST-style API
* Session-based authentication

Web

* HTML
* CSS
* JavaScript
* PHP

Windows

* Native Windows application
* Automated GitHub Actions builds

Android

* Android application
* Backend API integration
* Automated release builds

Security

Security is a core part of the project.

The application is designed to include:

* Password hashing
* Secure sessions
* CSRF protection
* SQL injection prevention
* XSS protection
* Input validation
* Rate limiting
* Authorization checks
* Secure API-key storage
* Protected configuration
* Safe error handling

Never commit API keys, passwords, database credentials, or other secrets to this repository.

Development

Clone the repository:

git clone https://github.com/YOUR-USERNAME/multi-ai-terminal.git
cd multi-ai-terminal

Configure your environment and database before running the application.

Configuration

Sensitive configuration should be stored outside the repository, such as environment variables or a protected server configuration.

Example:

DATABASE_HOST=
DATABASE_NAME=
DATABASE_USER=
DATABASE_PASSWORD=
AI_PROVIDER_KEY=

Do not commit real credentials.

Roadmap

* [ ]	Project structure
* [ ]	Database schema
* [ ]	User authentication
* [ ]	Web dashboard
* [ ]	AI chat system
* [ ]	Multi-provider support
* [ ]	Conversation history
* [ ]	API-key management
* [ ]	Usage limits
* [ ]	Security hardening
* [ ]	Production web deployment
* [ ]	Windows application
* [ ]	Android application
* [ ]	Automated Windows builds
* [ ]	Automated Android builds
* [ ]	Release system

Contributing

Contributions, suggestions, bug reports, and improvements are welcome.

Please open an issue before making major changes so that proposed changes can be discussed first.

License

This project is licensed under the MIT License.

See the LICENSE file for details.
