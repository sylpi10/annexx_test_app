/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import { Application } from '@hotwired/stimulus';
import LiveController from '@symfony/ux-live-component';

const app = Application.start();
app.register('live', LiveController);


console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
