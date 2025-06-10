// Simulazione database utenti
const mockUsers = [
  {
    id: "1",
    email: "paziente@test.com",
    role: "patient",
    firstName: "Mario",
    lastName: "Rossi",
    isFirstLogin: true,
    isPasswordResetRequired: true,
  },
  {
    id: "2",
    email: "terapista@test.com",
    role: "therapist",
    firstName: "Dr. Giulia",
    lastName: "Verdi",
    isFirstLogin: false,
    isPasswordResetRequired: false,
  },
  {
    id: "3",
    email: "paziente2@test.com",
    role: "patient",
    firstName: "Anna",
    lastName: "Bianchi",
    isFirstLogin: false,
    isPasswordResetRequired: false,
  },
];

// Simulazione API delay
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export const authService = {
  async login(credentials) {
    await delay(1000); // Simula network delay

    const user = mockUsers.find((u) => u.email === credentials.email);

    if (!user) {
      throw new Error("Credenziali non valide");
    }

    // In una vera app, qui verificheresti la password
    if (credentials.password !== "test123") {
      throw new Error("Credenziali non valide");
    }

    // Simula JWT token
    const token = `mock_jwt_token_${user.id}_${Date.now()}`;

    return {
      token,
      user,
    };
  },

  async resetPassword(userId, data) {
    await delay(500);

    if (data.password !== data.confirmPassword) {
      throw new Error("Le password non coincidono");
    }

    if (data.password.length < 6) {
      throw new Error("La password deve essere di almeno 6 caratteri");
    }

    // Trova l'utente e aggiorna lo stato
    const userIndex = mockUsers.findIndex((u) => u.id === userId);
    if (userIndex === -1) {
      throw new Error("Utente non trovato");
    }

    mockUsers[userIndex] = {
      ...mockUsers[userIndex],
      isFirstLogin: false,
      isPasswordResetRequired: false,
    };

    return mockUsers[userIndex];
  },

  async validateToken(token) {
    await delay(500);

    // Simula validazione JWT
    if (!token.startsWith("mock_jwt_token_")) {
      return null;
    }

    const userId = token.split("_")[3];
    const user = mockUsers.find((u) => u.id === userId);

    return user || null;
  },

  async logout() {
    await delay(300);
    // In una vera app, qui invalideresti il token dal server
  },
};
