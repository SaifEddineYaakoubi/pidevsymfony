import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// Custom controllers (non autoloaded via controllers.json)
import CrudSearchController from './controllers/crud_search_controller.js';
app.register('crud-search', CrudSearchController);

