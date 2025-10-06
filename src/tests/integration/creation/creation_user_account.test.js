import { createUser } from '../../utils.js';
import { randomFirstName, randomLastName, randomEmail, randomPasswordPair } from '../../utils.js';
// Récupérer la variable d'environnement
const API_URL = process.env.API_PLATFORM_URL;
if (!API_URL) {
  throw new Error('API_PLATFORM_URL is not defined in environment variables');
}

    describe('Create User Account with success', () => {
        
      test('should return 201 Created', async () => {

        const { response, payload } = await createUser(API_URL);
        const data = await response.json();

        expect(payload.plainPassword.length).toBeGreaterThan(7);
        expect(payload.plainPassword.length).toBeLessThanOrEqual(15);
        expect(payload.confirmationPassword.length).toBeGreaterThan(7);
        expect(payload.confirmationPassword.length).toBeLessThanOrEqual(15);
        expect(payload.plainPassword).toBe(payload.confirmationPassword);

        const regexPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/;
        expect(payload.plainPassword).toMatch(regexPassword);
        expect(payload.confirmationPassword).toMatch(regexPassword);


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
        expect(data.email).toEqual(payload.email);
        expect(data.firstName).toEqual(payload.firstName);
        expect(data.lastName).toEqual(payload.lastName);

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
        const regexString = /^[A-Za-zÀ-ÖØ-öø-ÿ]+(?:[ '-][A-Za-zÀ-ÖØ-öø-ÿ]+)*$/;

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


      it('should return 422 Unprocessable Entity for invalid email', async () => {
        const { response } = await createUser(API_URL, { email: 'invalid-email' });
        expect(response.status).toBe(422);
        expect(response.statusText).toEqual('Unprocessable Entity');
    });

      it('should return 422 Unprocessable Entity for email already', async () => {
        const usedEmail = 'xeuakt.uvtk0t@gmail.com';
        const { response } = await createUser(API_URL, { email: usedEmail });
        expect(response.status).toBe(422);
        expect(response.statusText).toEqual('Unprocessable Entity');
      });

      it('should return 422 Unprocessable Entity both passwords do not match', async () => {
        const { plainPassword } = randomPasswordPair();
        const { response } = await createUser(API_URL, { confirmationPassword: plainPassword + 'X' });
        expect(response.status).toBe(422);
        expect(response.statusText).toEqual('Unprocessable Entity');
    });

      it('should return 422 Unprocessable Entity for empty field', async () => {
        const { response } = await createUser(API_URL, { email: '' });
        expect(response.status).toBe(422);
        expect(response.statusText).toEqual('Unprocessable Entity');
      });

      it('should return 422 Unprocessable Entity for user creation with special characters', async () => {
        const { response } = await createUser(API_URL, { firstName: randomFirstName() + '😊' });
        expect(response.status).toBe(422);
        expect(response.statusText).toEqual('Unprocessable Entity');
      });
  });
