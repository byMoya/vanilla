var core_bootstrapApp=function(e){function t(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,t),r.l=!0,r.exports}var n={};return t.m=e,t.c=n,t.d=function(e,n,o){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:o})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="/js/",t(t.s=4)}([function(e,t){e.exports=lib_core_app},function(e,t,n){e.exports=n(0)(8)},function(e,t,n){e.exports=n(0)(166)},function(e,t,n){e.exports=n(0)(1)},function(e,t,n){"use strict";function o(e){return e&&e.__esModule?e:{default:e}}var r=n(1),u=n(2),a=n(5),c=o(n(10)),f=o(n(11));(0,u.debug)((0,r.getMeta)("debug",!1)),c.default.apiv2=f.default,(0,r.onContent)(function(e){(0,a._mountComponents)(e.target)}),(0,u.log)("Bootstrapping"),(0,r._executeReady)().then(function(){(0,u.log)("Bootstrapping complete.");var e=new CustomEvent("X-DOMContentReady",{bubbles:!0,cancelable:!1});document.dispatchEvent(e)}).catch(function(e){(0,u.logError)(e)})},function(e,t,n){"use strict";function o(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0}),t._mountComponents=function(e){(0,r.componentExists)("App")||(0,r.addComponent)("App",u.default);var t=e.querySelectorAll("[data-react]");Array.prototype.forEach.call(t,function(e){var t=e.getAttribute("data-react"),n=(0,r.getComponent)(t);n?f.default.render(c.default.createElement(n,null),e):(0,a.logError)("Could not find component %s.",t)})};var r=n(1),u=o(n(6)),a=n(2),c=o(n(3)),f=o(n(9))},function(e,t,n){"use strict";function o(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0});var r=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var o in n)Object.prototype.hasOwnProperty.call(n,o)&&(e[o]=n[o])}return e},u=function(){function e(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(t,n,o){return n&&e(t.prototype,n),o&&e(t,o),t}}(),a=o(n(3)),c=n(1),f=n(7),p=o(n(8)),i=function(e){function t(){return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,t),function(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}(t,a.default.PureComponent),u(t,[{key:"render",value:function(){var e=(0,c.getRoutes)().map(function(e){return a.default.createElement(e.type,r({key:e.key||e.props.path+(e.props.exact?"!":"")},e.props))});return e.push(a.default.createElement(f.Route,{key:"@not-found",component:p.default})),a.default.createElement(f.BrowserRouter,{basename:(0,c.getMeta)("context.basePath","")},a.default.createElement(f.Switch,null,e))}}]),t}();t.default=i},function(e,t,n){e.exports=n(0)(229)},function(e,t,n){e.exports=n(0)(257)},function(e,t,n){e.exports=n(0)(192)},function(e,t,n){e.exports=n(0)(11)},function(e,t,n){e.exports=n(0)(169)}]);
//# sourceMappingURL=core-bootstrap-app.js.map