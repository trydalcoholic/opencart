import { loader } from "../../../../assets/framework";

// Library
const session= loader.library('session');

// Config
const config = loader.config('default');

// Language
const language = loader.language('information/contact');

// Storage
const locations = loader.storage('information/location');

export default class {
    async connected() {

    }

    async render() {
        console.log('dfdf');

        let data = {};

        // Store Details
        data.store = config.config_name;
        data.image = config.config_image;
        data.address = config.config_address;
        data.telephone = config.config_telephone;

        // Location
        data.open = config.config_open;
        data.map = config.config_map;
        data.comment = config.config_comment;

        if (session.has('customer')) {
            let customer = session.get('customer');

            data.name = customer.get('firstname') + ' ' + customer.get('lastname');
            data.email = customer.get('email');
        } else {
            data.name = '';
            data.email = '';
        }

        data.locations = locations;

        return loader.template('information/contact', { ...data, ...language, ...config });
    }

    onSubmit(e) {
        e.preventDefault();


    }
}