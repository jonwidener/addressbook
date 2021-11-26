Vue.component('address-card', {
  props: ['id', 'name', 'addressLine1', 'addressLine2', 'city', 'state', 'zipCode'],
  template: `
    <div>
      <div>{{name}}</div>
      <div>{{addressLine1}}</div>
      <div>{{addressLine2}}</div>
      <div>
        {{city}}, {{state}} {{zipCode}}
      </div>
    </div>`
});

class Address {
  constructor(id=-1, name='', address_line_1='', address_line_2='', city='', state='', zip_code='', edit_mode=false) {
    this.id = id;
    this.name = name;
    this.address_line_1 = address_line_1;
    this.address_line_2 = address_line_2;
    this.city = city;
    this.state = state;
    this.zip_code = zip_code;
    this.edit_mode = edit_mode;
  }
}

var app = new Vue({
  el: '#app',

  data: {
    addresses: [],
    displayAddresses: [],
    undoAddresses: {},
    filter: '',
    theStates: [
      'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID',
      'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO',
      'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA',
      'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
     ]
  },
  created: function () {
    this.fetchData();    
  },
  methods: {
    fetchData: function () {
      var xhr = new XMLHttpRequest()
      var self = this
      xhr.open('POST', '/cgi/crud.php', true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
          var response = JSON.parse(xhr.responseText);
          for (const address of response) {
            self.addresses.push(new Address(
              address.id, address.name, address.address_line_1, address.address_line_2, 
              address.city, address.state, address.zip_code, false
            ));
          }
          self.applyFilter();
        }
      }
      xhr.send(JSON.stringify({ 
        schema: 'address_book', 
        mode: 'read', 
        table: 'addresses', 
        data: { 
          'id': '', 
          'name': '', 
          'address_line_1': '',
          'address_line_2': '',
          'city': '',
          'state': '',
          'zip_code': ''
          } 
        }));
    },
    updateFilter: function(value) {
      this.filter = value;
      this.applyFilter();
    },
    applyFilter: function() {
      this.displayAddresses = [];
      for (const address of this.addresses) {
        for (const attr in address) {
          if (['id', 'edit_mode'].includes(attr)) {
            continue;
          }
          if (address[attr].toUpperCase().includes(this.filter.toUpperCase())) {
            this.displayAddresses.push(address);
            break;
          }
        }
      }
    },
    onAddButton: function() {
      const temp = new Address();
      temp.edit_mode = true;
      this.displayAddresses.push(temp);
    },
    onEditButton: function(index) {
      this.displayAddresses[index].edit_mode = true;
      const address = this.displayAddresses[index];
      this.undoAddresses[address.id] = new Address(
        address.id, address.name, address.address_line_1, address.address_line_2, 
        address.city, address.state, address.zip_code, false
      );
    },
    onSaveButton: function(index) {
      if (this.displayAddresses[index].id != -1) {
        // update
        var xhr = new XMLHttpRequest()
        var self = this
        xhr.open('POST', '/cgi/crud.php', true);
        xhr.onreadystatechange = function () {
          if (xhr.readyState == 4 && xhr.status == 200) {
            var response = JSON.parse(xhr.responseText);
            // probably should do something with the response
            delete self.undoAddresses[self.displayAddresses[index].id];
            self.displayAddresses[index].edit_mode = false;
          }
        }
        xhr.send(JSON.stringify({ 
          schema: 'address_book', 
          mode: 'update', 
          table: 'addresses', 
          data: { 
            'name': this.displayAddresses[index].name, 
            'address_line_1': this.displayAddresses[index].address_line_1,
            'address_line_2': this.displayAddresses[index].address_line_2,
            'city': this.displayAddresses[index].city,
            'state': this.displayAddresses[index].state,
            'zip_code': this.displayAddresses[index].zip_code,
            'where': {'and': [['id', ['=', this.displayAddresses[index].id]]]}
          } 
        }));
      } else {
        // new        
        var xhr = new XMLHttpRequest()
        var self = this
        xhr.open('POST', '/cgi/crud.php', true);
        xhr.onreadystatechange = function () {
          if (xhr.readyState == 4 && xhr.status == 200) {
            var response = JSON.parse(xhr.responseText);
            // probably should do something with the response
            delete self.undoAddresses[self.displayAddresses[index].id];
            self.displayAddresses[index].edit_mode = false;
          }
        }
        xhr.send(JSON.stringify({ 
          schema: 'address_book', 
          mode: 'create', 
          table: 'addresses', 
          data: { 
            'name': this.displayAddresses[index].name, 
            'address_line_1': this.displayAddresses[index].address_line_1,
            'address_line_2': this.displayAddresses[index].address_line_2,
            'city': this.displayAddresses[index].city,
            'state': this.displayAddresses[index].state,
            'zip_code': this.displayAddresses[index].zip_code,
          } 
        }));
      }
    },
    onCancelButton: function(index) {
      if (this.displayAddresses[index].id != -1) {
        for (let attr in this.displayAddresses[index]) {
          this.displayAddresses[index][attr] = this.undoAddresses[this.displayAddresses[index].id][attr];
        }
        delete this.undoAddresses[this.displayAddresses[index].id];
      } else {
        this.displayAddresses.splice(index);
      }
    }
  }});