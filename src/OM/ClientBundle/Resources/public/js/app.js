function log(){console.log(arguments);}
var cook = {
    authToken: 'auth_token',
    backView: 'last_view',
    currView: 'curr_view'
}

var errors = {
    "22004": "INVALID_PARAMS",
    "22005": "UNIQUE_FAILED",
    "21001": "NOT_AUTHORIZED",
    "21002": "BAD_TOKEN",
    "21003": "BAD_CREDENTIALS",
    "21004": "USER_NOT_FOUND"
};

function APIError(msg, code) {
    this.name = 'APIError';
    this.message = msg || 'Some API error happened';
    this.code = code || 'UNKNOWN';
    this.getCode = function getCode() { return this.code; }
}
APIError.prototype = new Error;
APIError.prototype.constructor = APIError;

function API(baseUri) {
    function getBaseURI() { return baseUri; }
    function setBaseUri(uri) { baseUri = uri; }
    this.setBaseUri = setBaseUri;

    function request(method, path, params, cb) {
        var self = this;
        var data = method==='GET' ? params : JSON.stringify(params);
        $.ajax({
            type: method,
            url: getBaseURI() + path,
            contentType: "application/json",
            data: data,
            context: self
        }).always(function(data){
            if (data.responseText) data = JSON.parse(data.responseText);
            cb(data)
        });
    }
    //pages
    /**
     * Receive users widget
     * @param params object {page, size}
     * @param cb function(APIError, result)
     */
    function users(params, cb) {
        params.token = cookie.get(cook.authToken);
        request('GET', 'users', params, function(data){
            if(data.status != 0) {
                return cb(new APIError(data.message, data.status));
            }
            return cb(null, data.result);
        })
    }
    function messages() {
    }
    function options() {

    }
    function dialog() {

    }
    function profile() {

    }
    // actions
    function login(user, pass, cb) {
        request('GET', 'login', {username: user, password: pass}, function(data){
            if(data.status != 0) {
                cookie.del(cook.authToken);
                return cb(new APIError(data.message, data.status));
            }
            var token = data.result.token;
            cookie.set(cook.authToken, token);
            return cb(null, token);
        })
    }
    function checkAuth(cb) {
        var token = cookie.get(cook.authToken);
        request('GET', 'ok', {token: token}, function(data){
            return cb(null, data.status == 0);
        })
    }
    function register() {
    }
    function logout(cb) {
        var token = cookie.get(cook.authToken);
        request('GET', 'logout', {token: token}, function(data){
            cookie.del(cook.authToken);
            return cb&&cb(null, data.status == 0);
        });
    }

    function postMsg() {

    }
    function updateProfile() {

    }

    this.users = users;
    this.messages = messages;
    this.options = options;
    this.dialog = dialog;
    this.profile = profile;

    this.login = login;
    this.checkAuth = checkAuth;
    this.register = register;
    this.logout = logout;
    this.postMsg = postMsg;
    this.updateProfile = updateProfile;

    this.request = request;
}

function App(api) {
    var views = [
        'greet',
        'register',
        'login',
        'main'
    ];
    function toggleView(id) {
        var index = views.indexOf(id);
        if (index == -1) {
            console.log('Wrong view id: '+id);
            return;
        }

        cookie.set(cook.backView, views[$('.view.active').index()]);
        cookie.set(cook.currView, id);
        $('.view').removeClass('active');
        $('.view:eq('+index+')').addClass('active');
    }
    function init() {
        var token = cookie.get(cook.authToken);
        if (token) {
            return api.checkAuth(function(err, isAuthorized){
                if (isAuthorized) {
                    toggleView('main');
                }
            })
        }
        return 0;
    }
    this.init = init;
    this.toggleView = toggleView;
}

wait(
    function(){return typeof $ !== 'undefined';},
    function(){
        console.info('api loaded...');
        var api = new API('http://localhost:8000/app_dev.php/api/');
        var app = new App(api);
        window.app = app;
        window.api = api;
        app.init();
});
