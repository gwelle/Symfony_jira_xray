import { randomFirstName, randomLastName, randomEmail, randomPasswordPair } from '../../utils.js';
import fetch from 'node-fetch';

// Récupérer la variable d'environnement
const API_URL = process.env.API_PLATFORM_URL;
if (!API_URL) {
  throw new Error('API_PLATFORM_URL is not defined in environment variables');
}

const { plainPassword, confirmationPassword } = randomPasswordPair();
// Define the payload for creating a user account
const payload = JSON.stringify({
      email: randomEmail(),
      firstName: randomFirstName(),
      lastName: randomLastName(),
      plainPassword: plainPassword,
      confirmationPassword: confirmationPassword
  });

    describe('Create User Account with success', () => {
        test('should return 201 Created', async () => {

          expect(plainPassword.length).toBeGreaterThan(7);
          expect(plainPassword.length).toBeLessThanOrEqual(15);
          expect(confirmationPassword.length).toBeGreaterThan(7);
          expect(confirmationPassword.length).toBeLessThanOrEqual(15);
          expect(plainPassword).toBe(confirmationPassword);

          const regexPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/;
          expect(plainPassword).toMatch(regexPassword); 
          expect(confirmationPassword).toMatch(regexPassword);

          const response = await fetch(`${API_URL}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/ld+json',
              'Accept': 'application/ld+json'
            },
            body: payload
          });

          const data = await response.json();

          // Vérifie le code HTTP de la réponse 201 Created && le Content-Type
          expect(response.status).toEqual(201);

          const contentType = response.headers.get('content-type');
          expect(contentType).toMatch(/application\/ld\+json/);

          // Vérifie que les propriétés existent
          expect(data).toHaveProperty('email');
          expect(data).toHaveProperty('firstName');
          expect(data).toHaveProperty('lastName');
          expect(data).toHaveProperty('plainPassword');
          expect(data).toHaveProperty('confirmationPassword');

          // Vérifie que les valeurs correspondent à celles envoyées
          expect(data.email).toEqual(JSON.parse(payload).email);
          expect(data.firstName).toEqual(JSON.parse(payload).firstName);
          expect(data.lastName).toEqual(JSON.parse(payload).lastName);

          // Requête GET pour vérifier que l'utilisateur a bien été créé
          const getResponse = await fetch(`${API_URL}/${data.id}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
          });
          const getData = await getResponse.json();
          expect(getResponse.status).toEqual(200);
          expect(getData.email).toBe(data.email);
          expect(getData.firstName).toBe(data.firstName);
          expect(getData.lastName).toBe(data.lastName);

          // Vérifie que le corps de la réponse n'est pas vide
          expect(response.body).toBeDefined();
          expect(response.body).not.toBeNull();

          const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
          const regexString = /^[\p{L}]+(?:[ '-][\p{L}]+)*$/u;

          // Vérifie que les valeurs sont correctes
          expect(data.email).toMatch(regexEmail); 
          expect(data.firstName).toMatch(regexString); 
          expect(data.lastName).toMatch(regexString); 

          // Vérifie les longueurs des champs
          expect(data.email.length).toBeGreaterThan(5); 
          expect(data.firstName.length).toBeGreaterThan(0);
          expect(data.lastName.length).toBeGreaterThan(0);
        
          // Vérifie que les types sont corrects
          expect(typeof data.email).toBe('string');
          expect(typeof data.firstName).toBe('string');
          expect(typeof data.lastName).toBe('string');
          expect(typeof data.plainPassword).toBe('string');
          expect(typeof data.confirmationPassword).toBe('string');  
        });
    });
