import { startStimulusApp } from '@symfony/stimulus-bundle';
import PasswordVisibilityController from './controllers/password_visibility_controller.js';

const app = startStimulusApp();
app.register('password-visibility', PasswordVisibilityController);
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
