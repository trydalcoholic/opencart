import { WebComponent } from '../component.js';
import { loader } from '../index.js';

// Library
const currency = await loader.library('currency');

class XModal extends WebComponent {
    async render(){
        let data = {};

        // Add the data attributes to the data object
        //this.data.id = this.getAttribute('data-id');
        data.title = this.getAttribute('data-title');

        return `<div id="modal-security" class="modal show">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title text-danger" slot="title"><i class="fa-solid fa-triangle-exclamation"></i> {{ title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
             <div class="modal-body"></div>
            </div>
          </div>
        </div>`;
    }

    onOpen(e) {

    }

    onClick() {

    }
}

customElements.define('x-modal', XModal);