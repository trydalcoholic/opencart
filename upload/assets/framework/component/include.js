import { WebComponent } from '../component.js';
import { loader } from '../index.js';

// Config
const config = await loader.config('default');

class XInclude extends WebComponent {
    observed  = ['src'];

    get src() {
        console.log(this.getAttribute('src'));

        return this.getAttribute('src');
    }

    set src(src) {
        console.log('set src');
        console.log(src);

        //this.setAttribute('src', src);
    }

    async render() {
        // Get the source HTML to load
        //if (!this.src) return;

        let controller = await import(config.config_path + this.src + '.js');

        let test = new controller.default();

        this.innerHTML = await test.render();

        // Attach Events based on elements that have data-bind and data-on attributes
        let elements = this.querySelectorAll('[data-bind], [data-on]');

        for (let element of elements) {
            // Binds the element to an attribute by name.
            if (element.hasAttribute('data-bind')) {
                test['$' + element.getAttribute('data-bind')] = element;

                element.removeAttribute('data-bind');
            }

            if (element.hasAttribute('data-on')) {
                let part = element.getAttribute('data-on').split(':');

                if (part[1] !== undefined && part[1] in test) {
                    element.addEventListener(part[0], test[part[1]].bind(test));

                    element.removeAttribute('data-on');
                }
            }
        }
    }
}

customElements.define('x-include', XInclude);