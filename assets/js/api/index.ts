import { startStimulusApp } from 'vite-plugin-symfony/stimulus/helpers';
import {
  registerVueControllerComponents,
  type VueModule,
} from 'vite-plugin-symfony/stimulus/helpers/vue';

registerVueControllerComponents(
  import.meta.glob<VueModule>('./vue/controllers/**/*.vue'),
  './vue/controllers',
);

startStimulusApp();
