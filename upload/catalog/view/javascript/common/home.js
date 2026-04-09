import { loader } from '../index.js';

export default class {
    async connected() {

        this.innerHTML = loader.template('common/home', { ...data, ...language });
    }
}