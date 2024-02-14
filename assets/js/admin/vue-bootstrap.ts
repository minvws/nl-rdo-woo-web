import { startStimulusApp } from '@symfony/stimulus-bridge';
import { registerVueControllerComponents } from '@symfony/ux-vue';

registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/));

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
  '@symfony/stimulus-bridge/lazy-controller-loader!./vue/controllers',
  true,
  /\.[jt]sx?$/,
));
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
