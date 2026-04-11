export class WebComponent extends HTMLElement {
    constructor() {
        super();
    }

    async connectedCallback() {
        // Adds reactive component event changes to the attributes of the element to re-render the contents.
        for (let attribute of this.attributes) {
            if (!attribute.name.startsWith('data-')) {
                this.addEventListener('[' + attribute.name + ']', this.update.bind(this));
            }
        }

        if (this.connected !== undefined) {
            this.connected();
        }

        if (this.render !== undefined) {
            this.update();
        }
    }

    async update() {
        let test = await this.render();

        console.log('update');
        console.log(test);

        this.innerHTML = test;

            // Attach Events based on elements that have data-bind and data-on attributes
        let elements = this.querySelectorAll('[data-bind], [data-on]');

        for (let element of elements) {
            // Binds the element to an attribute by name.
            if (element.hasAttribute('data-bind')) {
                this['$' + element.getAttribute('data-bind')] = element;

                element.removeAttribute('data-bind');
            }

            if (element.hasAttribute('data-on')) {
                let part = element.getAttribute('data-on').split(':');

                if (part[1] !== undefined && part[1] in this) {
                    element.addEventListener(part[0], this[part[1]].bind(this));

                    element.removeAttribute('data-on');
                }
            }
        }
    }

    disconnectedCallback() {
        if (this.disconnected !== undefined) {
            this.disconnected();
        }
    }

    adoptedCallback() {
        if (this.adopted !== undefined) {
            this.adopted();
        }
    }

    static get observedAttributes() {
        console.log(this.observed);

        return this.observed;
    }

    attributeChangedCallback(name, value_old, value_new) {
        console.log(`${name} changed from ${value_old} to ${value_new}`);

        if (value_old != value_new) {
            let event = new CustomEvent('[' + name + ']', {
                bubbles: true,
                cancelable: true,
                detail: {
                    value_old: value_old,
                    value_new: value_new
                }
            });

            // Dispatch the event
            this.dispatchEvent(event);
        }
    }
}