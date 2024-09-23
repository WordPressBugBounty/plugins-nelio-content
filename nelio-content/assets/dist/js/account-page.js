(()=>{var e,n={4223:(e,n,t)=>{"use strict";t.r(n),t.d(n,{initPage:()=>cn});var o={};t.r(o),t.d(o,{getAccount:()=>g,getEditingLicense:()=>E,getInvoices:()=>b,getProducts:()=>y,getSiteId:()=>v,getSites:()=>x,isAgencySummary:()=>w,isDialogOpen:()=>_,isLocked:()=>h});var r={};t.r(r),t.d(r,{closeDialog:()=>P,enableAgencyFullView:()=>T,lock:()=>D,openDialog:()=>I,receiveAccount:()=>N,receiveInvoices:()=>O,receiveProducts:()=>k,receiveSites:()=>C,removeSite:()=>S,setEditingLicense:()=>L,unlock:()=>A});var i={};t.r(i),t.d(i,{applyLicense:()=>M,cancelSubscription:()=>K,createFreeSite:()=>G,reactivateSubscription:()=>$,removeLicense:()=>Y,upgradeSubscription:()=>H});var c={};t.r(c),t.d(c,{getAccount:()=>Q,getInvoices:()=>J,getProducts:()=>Z,getSites:()=>X});const a=window.wp.element,l=window.wp.components,s=window.wp.data;function u(e,n){return(0,s.useSelect)(e,n)}const m=window.lodash;var p={info:{plan:"free",siteId:(0,t(4396).L8)()(""),limits:{maxAutomationGroups:0,maxProfiles:0,maxProfilesPerNetwork:0}},invoices:[],products:[],sites:[],meta:{dialog:void 0,editingLicense:"",lockReason:void 0,isAgencySummary:!1}},d=function(){return d=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},d.apply(this,arguments)};const f=window.NelioContent.utils;function v(e){return e.info.siteId}function _(e,n){return e.meta.dialog===n}function h(e,n){return(0,f.isEmpty)(n)?!(0,f.isEmpty)(e.meta.lockReason):n===e.meta.lockReason}function E(e){return e.meta.editingLicense}function g(e){return e.info}function b(e){return e.invoices}function y(e){return e.products}function x(e){return e.sites||[]}function w(e){return e.meta.isAgencySummary}function N(e){return{type:"RECEIVE_ACCOUNT",account:e}}function C(e){return{type:"RECEIVE_SITES",sites:(0,m.castArray)(e)}}function S(e){return{type:"REMOVE_SITE",siteId:e}}function O(e){return{type:"RECEIVE_INVOICES",invoices:(0,m.castArray)(e)}}function k(e){return{type:"RECEIVE_PRODUCTS",products:(0,m.castArray)(e)}}function I(e){return{type:"OPEN_DIALOG",dialogName:e}}function P(){return{type:"CLOSE_DIALOG"}}function D(e){return void 0===e&&(e="no-reason"),{type:"LOCK_PAGE",reason:e}}function A(){return{type:"UNLOCK_PAGE"}}function L(e){return{type:"SET_EDITING_LICENSE",license:e}}function T(){return{type:"ENABLE_AGENCY_FULL_VIEW"}}const R=window.wp.apiFetch,j=t.n(R)(),F=window.wp.i18n,B=window.wp.notices;var U=function(e,n,t,o){return new(t||(t=Promise))((function(r,i){function c(e){try{l(o.next(e))}catch(e){i(e)}}function a(e){try{l(o.throw(e))}catch(e){i(e)}}function l(e){var n;e.done?r(e.value):(n=e.value,n instanceof t?n:new t((function(e){e(n)}))).then(c,a)}l((o=o.apply(e,n||[])).next())}))},V=function(e,n){var t,o,r,i={label:0,sent:function(){if(1&r[0])throw r[1];return r[1]},trys:[],ops:[]},c=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return c.next=a(0),c.throw=a(1),c.return=a(2),"function"==typeof Symbol&&(c[Symbol.iterator]=function(){return this}),c;function a(a){return function(l){return function(a){if(t)throw new TypeError("Generator is already executing.");for(;c&&(c=0,a[0]&&(i=0)),i;)try{if(t=1,o&&(r=2&a[0]?o.return:a[0]?o.throw||((r=o.return)&&r.call(o),0):o.next)&&!(r=r.call(o,a[1])).done)return r;switch(o=0,r&&(a=[2&a[0],r.value]),a[0]){case 0:case 1:r=a;break;case 4:return i.label++,{value:a[1],done:!1};case 5:i.label++,o=a[1],a=[0];continue;case 7:a=i.ops.pop(),i.trys.pop();continue;default:if(!((r=(r=i.trys).length>0&&r[r.length-1])||6!==a[0]&&2!==a[0])){i=0;continue}if(3===a[0]&&(!r||a[1]>r[0]&&a[1]<r[3])){i.label=a[1];break}if(6===a[0]&&i.label<r[1]){i.label=r[1],r=a;break}if(r&&i.label<r[2]){i.label=r[2],i.ops.push(a);break}r[2]&&i.ops.pop(),i.trys.pop();continue}a=n.call(e,i)}catch(e){a=[6,e],o=0}finally{t=r=0}if(5&a[0])throw a[1];return{value:a[0]?a[1]:void 0,done:!0}}([a,l])}}};function G(){return U(this,void 0,Promise,(function(){return V(this,(function(e){switch(e.label){case 0:return e.trys.push([0,2,,4]),[4,j({path:"/nelio-content/v1/site/free",method:"POST"})];case 1:case 3:return e.sent(),[3,4];case 2:return[4,W(e.sent())];case 4:return[2]}}))}))}function M(e){return U(this,void 0,Promise,(function(){return V(this,(function(n){switch(n.label){case 0:return n.trys.push([0,2,,6]),(0,s.dispatch)(te).lock("apply-license"),[4,j({path:"/nelio-content/v1/site/use-license",method:"POST",data:{license:e}})];case 1:return n.sent(),window.location.reload(),[3,6];case 2:return[4,W(n.sent())];case 3:return n.sent(),[4,(0,s.dispatch)(te).unlock()];case 4:return n.sent(),[4,(0,s.dispatch)(te).closeDialog()];case 5:return n.sent(),[3,6];case 6:return[2]}}))}))}function Y(e){return U(this,void 0,Promise,(function(){return V(this,(function(n){switch(n.label){case 0:return n.trys.push([0,4,,8]),[4,(0,s.dispatch)(te).lock("remove-license")];case 1:return n.sent(),[4,j({path:"/nelio-content/v1/site/remove-license",method:"POST",data:{siteId:e}})];case 2:return n.sent(),(0,s.select)(te).getSiteId()===e?(window.location.reload(),[2]):[4,(0,s.dispatch)(te).removeSite(e)];case 3:case 7:return n.sent(),[3,8];case 4:return[4,W(n.sent())];case 5:return n.sent(),[4,(0,s.dispatch)(te).unlock()];case 6:return n.sent(),[4,(0,s.dispatch)(te).closeDialog()];case 8:return[2]}}))}))}function K(){return U(this,void 0,Promise,(function(){return V(this,(function(e){switch(e.label){case 0:return e.trys.push([0,3,,7]),[4,(0,s.dispatch)(te).lock("cancel-subscription")];case 1:return e.sent(),[4,j({path:"/nelio-content/v1/subscription",method:"DELETE"})];case 2:return e.sent(),window.location.reload(),[3,7];case 3:return[4,W(e.sent())];case 4:return e.sent(),[4,(0,s.dispatch)(te).unlock()];case 5:return e.sent(),[4,(0,s.dispatch)(te).closeDialog()];case 6:return e.sent(),[3,7];case 7:return[2]}}))}))}function $(){return U(this,void 0,Promise,(function(){return V(this,(function(e){switch(e.label){case 0:return e.trys.push([0,3,,7]),[4,(0,s.dispatch)(te).lock("reactivate-subscription")];case 1:return e.sent(),[4,j({path:"/nelio-content/v1/subscription/uncancel",method:"POST"})];case 2:return e.sent(),window.location.reload(),[3,7];case 3:return[4,W(e.sent())];case 4:return e.sent(),[4,(0,s.dispatch)(te).unlock()];case 5:return e.sent(),[4,(0,s.dispatch)(te).closeDialog()];case 6:return e.sent(),[3,7];case 7:return[2]}}))}))}function H(e){return U(this,void 0,Promise,(function(){var n;return V(this,(function(t){switch(t.label){case 0:return t.trys.push([0,3,,6]),[4,(0,s.dispatch)(te).lock("upgrade-subscription")];case 1:return t.sent(),[4,j({path:"/nelio-content/v1/subscription/upgrade",method:"PUT",data:{product:e}})];case 2:return t.sent(),window.location.reload(),[3,6];case 3:return n=t.sent(),[4,(0,s.dispatch)(te).unlock()];case 4:return t.sent(),[4,W(n)];case 5:return t.sent(),[3,6];case 6:return[2]}}))}))}function W(e){return U(this,void 0,void 0,(function(){return V(this,(function(n){switch(n.label){case 0:return[4,(0,s.dispatch)(B.store).createErrorNotice(e.message||(0,F._x)("Something went wrong","text","nelio-content"))];case 1:return n.sent(),[2]}}))}))}var q=function(e,n,t,o){return new(t||(t=Promise))((function(r,i){function c(e){try{l(o.next(e))}catch(e){i(e)}}function a(e){try{l(o.throw(e))}catch(e){i(e)}}function l(e){var n;e.done?r(e.value):(n=e.value,n instanceof t?n:new t((function(e){e(n)}))).then(c,a)}l((o=o.apply(e,n||[])).next())}))},z=function(e,n){var t,o,r,i={label:0,sent:function(){if(1&r[0])throw r[1];return r[1]},trys:[],ops:[]},c=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return c.next=a(0),c.throw=a(1),c.return=a(2),"function"==typeof Symbol&&(c[Symbol.iterator]=function(){return this}),c;function a(a){return function(l){return function(a){if(t)throw new TypeError("Generator is already executing.");for(;c&&(c=0,a[0]&&(i=0)),i;)try{if(t=1,o&&(r=2&a[0]?o.return:a[0]?o.throw||((r=o.return)&&r.call(o),0):o.next)&&!(r=r.call(o,a[1])).done)return r;switch(o=0,r&&(a=[2&a[0],r.value]),a[0]){case 0:case 1:r=a;break;case 4:return i.label++,{value:a[1],done:!1};case 5:i.label++,o=a[1],a=[0];continue;case 7:a=i.ops.pop(),i.trys.pop();continue;default:if(!((r=(r=i.trys).length>0&&r[r.length-1])||6!==a[0]&&2!==a[0])){i=0;continue}if(3===a[0]&&(!r||a[1]>r[0]&&a[1]<r[3])){i.label=a[1];break}if(6===a[0]&&i.label<r[1]){i.label=r[1],r=a;break}if(r&&i.label<r[2]){i.label=r[2],i.ops.push(a);break}r[2]&&i.ops.pop(),i.trys.pop();continue}a=n.call(e,i)}catch(e){a=[6,e],o=0}finally{t=r=0}if(5&a[0])throw a[1];return{value:a[0]?a[1]:void 0,done:!0}}([a,l])}}};function Q(){return q(this,void 0,Promise,(function(){var e;return z(this,(function(n){switch(n.label){case 0:return[4,j({path:"/nelio-content/v1/site"})];case 1:return e=n.sent(),[4,(0,s.dispatch)(te).receiveAccount(e)];case 2:return n.sent(),[2]}}))}))}function J(){return q(this,void 0,Promise,(function(){var e;return z(this,(function(n){switch(n.label){case 0:return n.trys.push([0,3,,5]),[4,j({path:"/nelio-content/v1/subscription/invoices"})];case 1:return e=n.sent(),[4,(0,s.dispatch)(te).receiveInvoices(e)];case 2:case 4:return n.sent(),[3,5];case 3:return n.sent(),[4,(0,s.dispatch)(te).receiveInvoices([])];case 5:return[2]}}))}))}function X(){return q(this,void 0,Promise,(function(){var e;return z(this,(function(n){switch(n.label){case 0:return n.trys.push([0,3,,5]),[4,j({path:"/nelio-content/v1/subscription/sites"})];case 1:return e=n.sent(),[4,(0,s.dispatch)(te).receiveSites(e)];case 2:case 4:return n.sent(),[3,5];case 3:return n.sent(),[4,(0,s.dispatch)(te).receiveSites([])];case 5:return[2]}}))}))}function Z(){return q(this,void 0,Promise,(function(){var e;return z(this,(function(n){switch(n.label){case 0:return n.trys.push([0,3,,5]),[4,j({path:"/nelio-content/v1/products"})];case 1:return e=n.sent(),[4,(0,s.dispatch)(te).receiveProducts(e)];case 2:case 4:return n.sent(),[3,5];case 3:return n.sent(),[4,(0,s.dispatch)(te).receiveProducts([])];case 5:return[2]}}))}))}var ee=function(){return ee=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},ee.apply(this,arguments)},ne=ee(ee({},r),i),te=(0,s.createReduxStore)("nelio-content/account",{reducer:function(e,n){var t;return void 0===e&&(e=p),null!==(t=function(e,n){switch(n.type){case"RECEIVE_ACCOUNT":return d(d({},e),{info:n.account,meta:d(d({},e.meta),{isAgencySummary:"free"!==n.account.plan&&!!n.account.isAgency})});case"RECEIVE_PRODUCTS":return d(d({},e),{products:n.products});case"RECEIVE_SITES":return d(d({},e),{sites:n.sites});case"REMOVE_SITE":return d(d({},e),{sites:(0,m.reject)(e.sites,{id:n.siteId})});case"RECEIVE_INVOICES":return d(d({},e),{invoices:n.invoices});case"OPEN_DIALOG":return d(d({},e),{meta:d(d({},e.meta),{dialog:n.dialogName})});case"CLOSE_DIALOG":return d(d({},e),{meta:d(d({},e.meta),{dialog:void 0})});case"ENABLE_AGENCY_FULL_VIEW":return d(d({},e),{meta:d(d({},e.meta),{isAgencySummary:!1})});case"LOCK_PAGE":return d(d({},e),{meta:d(d({},e.meta),{lockReason:n.reason})});case"UNLOCK_PAGE":return d(d({},e),{meta:d(d({},e.meta),{lockReason:void 0})});case"SET_EDITING_LICENSE":return d(d({},e),{meta:d(d({},e.meta),{editingLicense:n.license})})}}(e,n))&&void 0!==t?t:e},controls:s.controls,actions:ne,selectors:o,resolvers:c});!function(e){(0,s.register)(e)}(te);const oe=window.NelioContent.components;var re=function(e){var n=e.children,t=ie(),o=ce(),r=o.notices,i=o.removeNotice;return t?a.createElement(oe.LoadingAnimation,{text:(0,F._x)("Loading…","text","nelio-content")}):a.createElement(a.Fragment,null,a.createElement(l.NoticeList,{notices:r,className:"components-editor-notices__pinned",onRemove:i}),n)},ie=function(){return u((function(e){return e(te).getAccount(),!e(te).hasFinishedResolution("getAccount")}))},ce=function(){return{notices:u((function(e){return e(B.store).getNotices()})),removeNotice:(0,s.useDispatch)(B.store).removeNotice}},ae=t(6942),le=t.n(ae);const se=window.NelioContent.date;var ue=(0,F._x)("Y-m-d","text (date)","nelio-content"),me=function(){var e=u((function(e){return e(te).getAccount()}));if("free"===e.plan)return null;var n=e.mode,t=e.deactivationDate,o=e.nextChargeDate,r=e.nextChargeTotal,i=e.state;return"invitation"===n?a.createElement("div",{className:"nelio-content-plan__renewal"},(0,F._x)("You’re currently using a Free Pass to Nelio Content’s Premium Features. Enjoy the plugin and, please, help us improve it with your feedback!","text","nelio-content")):"canceled"===i?a.createElement("div",{className:"nelio-content-plan__renewal"},(0,a.createInterpolateElement)((0,F.sprintf)(/* translators: a date */ /* translators: a date */
(0,F._x)("Your subscription will end on %s.","text","nelio-content"),"<date>".concat((0,se.dateI18n)(ue,t),"</date>")),{date:a.createElement("span",{className:"nelio-content-plan__renewal-date"})})):a.createElement("div",{className:"nelio-content-plan__renewal"},(0,a.createInterpolateElement)((0,F.sprintf)(/* translators: 1 -> price and currency; 2 -> date */ /* translators: 1 -> price and currency; 2 -> date */
(0,F._x)("Next charge will be %1$s on %2$s.","text","nelio-content"),"<money>".concat(r,"</money>"),"<date>".concat((0,se.dateI18n)(ue,o),"</date>")),{date:a.createElement("span",{className:"nelio-content-plan__renewal-date"}),money:a.createElement("span",{className:"nelio-content-plan__renewal-amount"})}))},pe=function(e){var n,t=e.plan,o=e.period,r=e.isCanceled,i=e.isInvitation;return a.createElement("h3",{className:"nelio-content-plan__title"},null!==(n=de(t))&&void 0!==n?n:de("basic"),a.createElement("span",{className:"nelio-content-plan__period"},i&&(0,F._x)("Invitation","text","nelio-content"),!i&&"month"===o&&(0,F._x)("Monthly","text","nelio-content"),!i&&"year"===o&&(0,F._x)("Yearly","text","nelio-content")),r&&a.createElement("span",{className:"nelio-content-plan__state-canceled"},(0,F._x)("Canceled","text (account state)","nelio-content")))};function de(e){switch(e){case"free":return(0,F._x)("Nelio Content (Free)","text","nelio-content");case"plus":return(0,F._x)("Nelio Content Plus","text","nelio-content");case"standard":return(0,F._x)("Nelio Content Standard","text","nelio-content");case"basic":return(0,F._x)("Nelio Content Basic","text","nelio-content")}}var fe=function(e){var n=u((function(n){return n(te).isDialogOpen(e)})),t=(0,s.useDispatch)(te),o=t.openDialog,r=t.closeDialog;return[n,function(n){return n?o(e):r()}]},ve=function(e){return u((function(n){return n(te).isLocked(e)}))},_e=function(){return u((function(e){var n=(0,e(te).getAccount)();if("free"===n.plan)return[];var t=e(te).getProducts;return(0,m.filter)(t(),(function(e){return e.upgradeableFrom.includes(n.productId)}))}))},he=function(e){var n=e.isOpen,t=e.onFocusOutside,o=e.placement,r=Ee(),i=r[0],c=r[1],u=ve(),m=ve("apply-license"),p=(0,s.useDispatch)(te).applyLicense;return n?a.createElement(l.Popover,{placement:o,onFocusOutside:!u&&t?t:void 0},a.createElement("div",{className:"nelio-content-license-form"},a.createElement(l.TextControl,{value:i,placeholder:(0,F._x)("Type your license here","user","nelio-content"),maxLength:Math.max(21,26),className:"nelio-content-license-form__text-control",disabled:u,onChange:c}),a.createElement(l.Button,{variant:"primary",isBusy:m,className:"nelio-content-license-form__button",disabled:u||21!==i.length&&26!==i.length,onClick:function(){return p(i)}},m?(0,F._x)("Applying…","text","nelio-content"):(0,F._x)("Apply","command","nelio-content")))):null},Ee=function(){return[u((function(e){return e(te).getEditingLicense()})),(0,s.useDispatch)(te).setEditingLicense]},ge=function(){return ge=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},ge.apply(this,arguments)},be=function(e){var n=e.label,t=function(e,n){var t={};for(var o in e)Object.prototype.hasOwnProperty.call(e,o)&&n.indexOf(o)<0&&(t[o]=e[o]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var r=0;for(o=Object.getOwnPropertySymbols(e);r<o.length;r++)n.indexOf(o[r])<0&&Object.prototype.propertyIsEnumerable.call(e,o[r])&&(t[o[r]]=e[o[r]])}return t}(e,["label"]),o=fe("license-popover"),r=o[0],i=o[1],c=ve();return a.createElement("span",null,a.createElement(l.Button,ge({disabled:c,onClick:function(){return i(!0)}},t),n),a.createElement(he,{placement:"bottom",onFocusOutside:function(){return i(!1)},isOpen:r}))},ye=(0,F._x)("Y-m-d","text (date)","nelio-content"),xe=function(){var e=fe("cancel-subscription"),n=e[0],t=e[1],o=ve("cancel-subscription"),r=ve(),i=u((function(e){var n=e(te).getAccount();return"free"===n.plan?"":n.nextChargeDate})),c=(0,s.useDispatch)(te).cancelSubscription;return a.createElement(a.Fragment,null,a.createElement(l.Button,{variant:"tertiary",isDestructive:!0,onClick:function(){return t(!0)},disabled:r},(0,F._x)("Cancel Subscription","command","nelio-content")),a.createElement(oe.ConfirmationDialog,{title:(0,F._x)("Cancel Subscription?","text","nelio-content"),text:(0,F.sprintf)(/* translators: a date */ /* translators: a date */
(0,F._x)("Canceling your subscription will cause it not to renew. If you cancel your subscrition, it will continue until %s. Then, the subscription will expire and will not be invoiced again. Do you want to cancel your subscription?","user","nelio-content"),(0,se.dateI18n)(ye,i)),confirmLabel:o?(0,F._x)("Canceling…","text","nelio-content"):(0,F._x)("Cancel Subscription","command","nelio-content"),cancelLabel:(0,F._x)("Back","command","nelio-content"),isDestructive:!0,onCancel:function(){return t(!1)},onConfirm:c,isConfirmEnabled:!r,isCancelEnabled:!r,isOpen:n,isConfirmBusy:o}))},we=(0,F._x)("Y-m-d","text (date)","nelio-content"),Ne=function(){var e=u((function(e){return e(te).isLocked()})),n=u((function(e){return e(te).isDialogOpen("reactivate-subscription")})),t=u((function(e){return e(te).isLocked("reactivate-subscription")})),o=u((function(e){var n=e(te).getAccount();return"free"===n.plan?"":n.nextChargeDate})),r=(0,s.useDispatch)(te),i=r.reactivateSubscription,c=r.openDialog,m=r.closeDialog;return a.createElement(a.Fragment,null,a.createElement(l.Button,{variant:"primary",onClick:function(){return c("reactivate-subscription")},disabled:e},(0,F._x)("Reactivate Subscription","command","nelio-content")),a.createElement(oe.ConfirmationDialog,{title:(0,F._x)("Reactivate Subscription?","text","nelio-content"),text:(0,F.sprintf)(/* translators: a date */ /* translators: a date */
(0,F._x)("Reactivating your subscription will cause it to renew on %s. Do you want to reactivate your subscription?","user","nelio-content"),(0,se.dateI18n)(we,o)),confirmLabel:t?(0,F._x)("Reactivating…","text","nelio-content"):(0,F._x)("Reactivate Subscription","command","nelio-content"),cancelLabel:(0,F._x)("Back","command","nelio-content"),onCancel:m,onConfirm:i,isConfirmEnabled:!e,isCancelEnabled:!e,isOpen:n,isConfirmBusy:t}))},Ce=function(){return Ce=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},Ce.apply(this,arguments)},Se=function(e){var n=e.isOpen,t=e.placement,o=e.onUpgrade,r=e.onFocusOutside,i=(0,a.useState)(),c=i[0],u=i[1],m=_e(),p=Ie(),d=(0,s.useDispatch)(te).upgradeSubscription;return n?p?a.createElement(l.Popover,{className:"nelio-content-upgrade-form--loading",noArrow:!1,placement:t,onFocusOutside:r},a.createElement(l.Spinner,null)):a.createElement(l.Popover,{className:"nelio-content-upgrade-form",noArrow:!1,placement:t,onFocusOutside:r},a.createElement(l.RadioControl,{className:"nelio-content-upgrade-form__product-container",label:(0,F._x)("Subscription Plans","text","nelio-content"),selected:c,options:m.map((function(e){return{label:a.createElement(Oe,Ce({},e)),value:e.id}})),onChange:u}),a.createElement("div",{className:"nelio-content-upgrade-form__button-container"},a.createElement(l.Button,{variant:"primary",className:"nelio-content-upgrade-form__button",disabled:!c,onClick:function(){c&&(o(),d(c))}},(0,F._x)("Upgrade","command","nelio-content")))):null},Oe=function(e){var n=e.displayName,t=e.price,o=e.description,r=ke();return a.createElement("div",{className:"nelio-content-upgrade-form__product"},a.createElement("strong",{className:"nelio-content-upgrade-form__product-name"},De(n)),a.createElement("span",{className:"nelio-content-upgrade-form__product-price",title:r},Pe(t[r],r)),a.createElement("span",{className:"nelio-content-upgrade-form__product-description"},De(o)))},ke=function(){return u((function(e){var n=e(te).getAccount();return"free"===n.plan?"USD":n.currency||"USD"}))},Ie=function(){return u((function(e){return!e(te).hasFinishedResolution("getProducts")}))},Pe=function(e,n){return"EUR"===n?"".concat(null!=e?e:"—","€"):"$".concat(null!=e?e:"—")},De=function(e){return e[(0,f.getShortLocale)()]||e.en},Ae=function(){var e=(0,a.useState)(!1),n=e[0],t=e[1],o=ve(),r=ve("upgrade-subscription");return _e().length?a.createElement("div",null,a.createElement(l.Button,{variant:"primary",isBusy:r,onClick:function(){return t(!0)},disabled:o},r?(0,F._x)("Upgrading…","text","nelio-content"):(0,F._x)("Upgrade Subscription","command","nelio-content")),a.createElement(Se,{isOpen:n,placement:"bottom",onFocusOutside:function(){return t(!1)},onUpgrade:function(){return t(!1)}})):null},Le=function(){return Le=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},Le.apply(this,arguments)},Te=function(e){var n=e.label,t=function(e,n){var t={};for(var o in e)Object.prototype.hasOwnProperty.call(e,o)&&n.indexOf(o)<0&&(t[o]=e[o]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var r=0;for(o=Object.getOwnPropertySymbols(e);r<o.length;r++)n.indexOf(o[r])<0&&Object.prototype.propertyIsEnumerable.call(e,o[r])&&(t[o[r]]=e[o[r]])}return t}(e,["label"]),o=fe("remove-license"),r=o[0],i=o[1],c=u((function(e){return e(te).getSiteId()})),m=ve("remove-license"),p=ve(),d=(0,s.useDispatch)(te).removeLicense;return a.createElement(a.Fragment,null,a.createElement(l.Button,Le({onClick:function(){return i(!0)},disabled:p},t),n),a.createElement(oe.ConfirmationDialog,{title:(0,F._x)("Downgrade to Free Version?","text","nelio-content"),text:(0,F._x)("This action will remove the license from this site so that you can use it somewhere else. Nelio Content will remain active on this site, but you will be using the free version instead. This might result in some scheduled social messages being lost. Do you want to continue?","user","nelio-content"),confirmLabel:m?(0,F._x)("Downgrading…","text (remove license)","nelio-content"):(0,F._x)("Downgrade","command (remove license)","nelio-content"),cancelLabel:(0,F._x)("Back","command","nelio-content"),isDestructive:!0,onCancel:function(){return i(!1)},onConfirm:function(){d(c)},isConfirmEnabled:!p,isCancelEnabled:!p,isOpen:r,isConfirmBusy:m}))};window.NelioContent.data;const Re=window.wp.url;var je=function(){return a.createElement(l.ExternalLink,{className:"components-button is-primary",href:(0,Re.addQueryArgs)("https://twitter.com/intent/tweet",{text:(0,F._x)("Nelio Content is an awesome #EditorialCalendar for #WordPress by @NelioSoft!","text","nelio-content")})},(0,F._x)("Tweet About Nelio Content","command","nelio-content"))},Fe=function(){var e=u((function(e){return e(te).getAccount()}));if("free"===e.plan)return null;var n=e.mode,t=e.state,o=e.plan,r=e.period;return a.createElement("div",{className:"nelio-content-account-container__box nelio-content-plan"},a.createElement("div",{className:"nelio-content-plan__content"},a.createElement(pe,{isCanceled:"canceled"===t,isInvitation:"invitation"===n,plan:o,period:r}),a.createElement(me,null)),a.createElement("div",{className:"nelio-content-plan__actions"},"invitation"===n&&a.createElement(je,null),"invitation"!==n&&"active"===t&&a.createElement(a.Fragment,null,a.createElement(xe,null),a.createElement(Ae,null)),"invitation"!==n&&"canceled"===t&&a.createElement(Ne,null)))},Be=function(){var e=u((function(e){return e(te).getAccount()}));if("free"===e.plan)return null;var n=e.plan,t=e.period,o=e.state,r=e.mode;return a.createElement("div",{className:"nelio-content-account-container__box nelio-content-plan"},a.createElement("div",{className:"nelio-content-plan__content"},a.createElement(pe,{isCanceled:"canceled"===o,isInvitation:"invitation"===r,plan:n,period:t}),a.createElement("div",{className:"nelio-content-plan__renewal"},(0,F._x)("You’re currently using an agency subscription plan.","user","nelio-content"))),a.createElement("div",{className:"nelio-content-plan__actions"},a.createElement(Ue,null),a.createElement(Ve,null)))},Ue=function(){var e=Ge(),n=(0,a.useState)(!1),t=n[0],o=n[1],r=(0,s.useDispatch)(te).removeLicense,i=Me(),c=function(){return o(!1)};return a.createElement(a.Fragment,null,a.createElement(l.Button,{isDestructive:!0,className:"nelio-content-plan__action",onClick:function(){return o(!0)},disabled:i},(0,F._x)("Downgrade to Free Version","command","nelio-content")),a.createElement(oe.ConfirmationDialog,{title:(0,F._x)("Downgrade to Free version?","text","nelio-content"),text:(0,F._x)("This will remove the subscription license from the site and you’ll be using Nelio Content’s free version.","text","nelio-content"),confirmLabel:(0,F._x)("Downgrade","command","nelio-content"),isOpen:t,onCancel:c,onConfirm:function(){c(),r(e)}}))},Ve=function(){var e=u((function(e){return e(te).getAccount()})),n=Me(),t=(0,a.useState)(!1),o=t[0],r=t[1],i=(0,a.useState)(""),c=i[0],m=i[1],p=(0,s.useDispatch)(te).enableAgencyFullView;if("free"===e.plan)return null;var d=function(){return r(!1)};return a.createElement("div",null,a.createElement(l.Button,{variant:"secondary",className:"nelio-content-plan__action",onClick:function(){return r(!0)},disabled:n},(0,F._x)("View Details","command","nelio-content")),o&&a.createElement(l.Popover,{noArrow:!1,placement:"bottom-start",onFocusOutside:d},a.createElement("div",{className:"nelio-content-license-form"},a.createElement(l.TextControl,{value:c,placeholder:(0,F._x)("Type your license here","user","nelio-content"),className:"nelio-content-license-form__text-control",onChange:m}),a.createElement(l.Button,{variant:"primary",className:"nelio-content-license-form__button",onClick:function(){e.license&&c===e.license?(p(),d()):m("")},disabled:c.length!==e.license.length},(0,F._x)("Validate","command","nelio-content")))))},Ge=function(){return u((function(e){return e(te).getSiteId()}))},Me=function(){return u((function(e){return e(te).isLocked()}))},Ye=(0,F._x)("Y-m-d","text (date)","nelio-content"),Ke=function(){var e=u((function(e){return e(te).getAccount()}));if("free"===e.plan)return null;var n=e.creationDate,t=e.firstname,o=e.lastname,r=e.license,i=e.email,c=e.photo,s=(0,F.sprintf)(/* translators: 1 -> first name, 2 -> lastname */ /* translators: 1 -> first name, 2 -> lastname */
(0,F._x)("%1$s %2$s","text (full name)","nelio-content"),t,o),m=(0,f.getFirstLatinizedLetter)(s)||"a";return a.createElement("div",{className:"nelio-content-account-container__box nelio-content-info"},a.createElement("h3",{className:"nelio-content-info__title"},(0,F._x)("Account Information","title (account)","nelio-content")),a.createElement("div",{className:"nelio-content-info__container"},a.createElement("div",{className:"nelio-content-info__profile"},a.createElement("div",{className:"nelio-content-info__picture nelio-content-first-letter-".concat(m)},a.createElement("div",{className:"nelio-content-info__actual-picture",style:{backgroundImage:"url(".concat(c,")")}}))),a.createElement("div",{className:"nelio-content-info__details"},a.createElement("p",{className:"nelio-content-info__name"},s),a.createElement("p",{className:"nelio-content-info__email"},a.createElement(l.Dashicon,{icon:"email",className:"nelio-content-info__icon"}),i),a.createElement("p",{className:"nelio-content-info__creation-date"},a.createElement(l.Dashicon,{icon:"calendar",className:"nelio-content-info__icon"}),(0,a.createInterpolateElement)((0,F.sprintf)(/* translators: a date */ /* translators: a date */
(0,F._x)("Member since %s.","text","nelio-content"),"<date>".concat((0,se.dateI18n)(Ye,n),"</date>")),{date:a.createElement("strong",null)})),a.createElement("div",{className:"nelio-content-info__license"},a.createElement(l.Dashicon,{icon:"admin-network",className:"nelio-content-info__icon"}),a.createElement("code",{title:(0,F._x)("License Key","text","nelio-content")},r),a.createElement("div",{className:"nelio-content-info__change-license"},a.createElement(be,{variant:"link",label:(0,F._x)("Change","command","nelio-content")})),a.createElement("div",{className:"nelio-content-info__remove-license"},a.createElement(Te,{variant:"link",isDestructive:!0,label:(0,F._x)("Remove","command","nelio-content")}))))))},$e=function(e){var n=e.invoiceUrl,t=e.reference,o=e.chargeDate,r=e.isRefunded,i=e.subtotalDisplay;return a.createElement("tr",{className:"nelio-content-invoice"},a.createElement("td",{className:"nelio-content-invoice__reference"},a.createElement("a",{href:n,className:"nelio-content-invoice__link",target:"_blank",rel:"noopener noreferrer"},t)),a.createElement("td",{className:"nelio-content-invoice__date"},o),a.createElement("td",{className:"nelio-content-invoice__total"},r&&a.createElement("span",{className:"nelio-content-invoice__label"},(0,F._x)("(Refunded)","text (invoice)","nelio-content")),a.createElement("span",{className:le()({"nelio-content-invoice__total-value":!0,"nelio-content-invoice__total-value--refunded":r})},i)))},He=function(){return He=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},He.apply(this,arguments)},We=function(){var e=qe(),n=ze(),t=Qe(),o=Je();return t?n?a.createElement("div",{className:"nelio-content-account-container__box nelio-content-billing"},a.createElement("h3",{className:"nelio-content-billing__title"},(0,F._x)("Billing History","text","nelio-content"),n&&a.createElement(l.Spinner,null))):a.createElement("div",{className:"nelio-content-account-container__box nelio-content-billing"},a.createElement("h3",{className:"nelio-content-billing__title"},(0,F._x)("Billing History","text","nelio-content")),!!o&&a.createElement(l.ExternalLink,{className:"nelio-content-billing__action",href:o},(0,F._x)("Manage Payments","command","nelio-content")),a.createElement("table",{className:"nelio-content-billing__container"},a.createElement("thead",null,a.createElement("tr",null,a.createElement("th",{className:"nelio-content-billing__reference"},(0,F._x)("Invoice Reference","text (account, billing table title)","nelio-content")),a.createElement("th",{className:"nelio-content-billing__date"},(0,F._x)("Date","text (account, billing table title)","nelio-content")),a.createElement("th",{className:"nelio-content-billing__total"},(0,F._x)("Total","text (account, billing table title)","nelio-content")))),a.createElement("tbody",{className:"invoice-list"},e.map((function(e){return a.createElement($e,He({key:e.reference},e))}))))):null},qe=function(){return u((function(e){return e(te).getInvoices()||[]}))},ze=function(){return u((function(e){return!e(te).hasFinishedResolution("getInvoices")}))},Qe=function(){return u((function(e){var n=e(te).getAccount();return"free"!==n.plan&&"regular"===n.mode}))},Je=function(){return u((function(e){var n=e(te).getAccount();return"free"===n.plan?"":n.urlToManagePayments}))},Xe=function(e){var n=e.site,t=(0,a.useState)(!1),o=t[0],r=t[1],i=(0,s.useDispatch)(te).removeLicense,c=u((function(e){return e(te).isLocked()})),m=n.isCurrentSite,p=n.url,d=n.actualUrl;return a.createElement("li",{className:"nelio-content-site"},m?a.createElement("span",{className:"nelio-content-site__url"},a.createElement("span",{className:"nelio-content-site__current-site"},d,!!d&&p!==d&&a.createElement(oe.HelpIcon,{className:"nelio-content-site__current-site-help",type:"info",text:(0,F.sprintf)(/* translators: site URL */ /* translators: site URL */
(0,F._x)("Activation URL is “%s”","text","nelio-content"),p)})),a.createElement("span",{className:"nelio-content-site__label"},(0,F._x)("This site","text","nelio-content"))):a.createElement(a.Fragment,null,a.createElement("span",{className:"nelio-content-site__url"},a.createElement("a",{href:p,className:le()("nelio-content-site__link",{"nelio-content-site__link--current":m}),target:"_blank",rel:"noopener noreferrer"},p)),a.createElement(l.Button,{isDestructive:!0,variant:"tertiary",className:"nelio-content-site__unlink-button",onClick:function(){return r(!0)},disabled:c},(0,F._x)("Unlink Site","command","nelio-content")),a.createElement(oe.ConfirmationDialog,{title:(0,F._x)("Unlink Site?","text","nelio-content"),text:(0,F._x)("This will remove the subscription license from the site.","text","nelio-content"),confirmLabel:(0,F._x)("Unlink","command","nelio-content"),isOpen:o,onCancel:function(){return r(!1)},onConfirm:function(){r(!1),i(n.id)}})))},Ze=function(){var e=en(),n=tn(),t=nn(),o=t.length;return n<=1?null:a.createElement("div",{className:"nelio-content-account-container__box nelio-content-sites"},a.createElement("h3",{className:"nelio-content-sites__title"},(0,F._x)("Sites","text","nelio-content"),e&&a.createElement(l.Spinner,null),a.createElement("span",{className:"nelio-content-sites__availability"},!e&&"".concat(o," / ").concat(n))),a.createElement("ul",{className:"nelio-content-sites__list"},!e&&t.map((function(e){return a.createElement(Xe,{key:e.id,site:e})}))))},en=function(){return u((function(e){return!e(te).hasFinishedResolution("getSites")}))},nn=function(){return u((function(e){return e(te).getSites()||[]}))},tn=function(){return u((function(e){var n=e(te).getAccount();return"free"===n.plan?1:n.sitesAllowed}))},on=function(){var e=u((function(e){return e(te).getAccount()}));if(!u((function(e){return e(te).hasFinishedResolution("getAccount")})))return null;if("free"===e.plan)return a.createElement("div",{className:"nelio-content-account-container nelio-content-account-container--free-user"},a.createElement(oe.SubscriptionRequiredPage,{page:"account"}));if(e.isAgency)return a.createElement("div",{className:"nelio-content-account-container nelio-content-account-container--is-agency-summary"},a.createElement(Be,null));var n="invitation"===e.mode,t=1<e.sitesAllowed;return a.createElement("div",{className:le()("nelio-content-account-container",{"nelio-content-account-container--is-invitation":n,"nelio-content-account-container--is-subscribed":!n,"nelio-content-account-container--is-multi-site":t})},a.createElement(Fe,null),a.createElement(Ke,null),a.createElement(Ze,null),a.createElement(We,null))},rn=function(e){var n,t=e.isSubscribed,o=e.siteId,r=(0,a.useState)(!1),i=r[0],c=r[1];return(0,a.useEffect)((function(){var e=setTimeout((function(){return c(!1)}),1500);return function(){return clearTimeout(e)}}),[i]),a.createElement("h1",{className:"wp-heading-inline nelio-content-page-title"},a.createElement("span",null,t?(0,F._x)("Account Details","text","nelio-content"):(0,F._x)("Upgrade to Premium","user","nelio-content")),a.createElement("span",{className:"nelio-content-page-title__support-key"},a.createElement("strong",null,(0,F._x)("Support Key:","text","nelio-content")),a.createElement("code",null,o),!!(null===(n=navigator.clipboard)||void 0===n?void 0:n.writeText)&&a.createElement(l.Button,{icon:"admin-page",onClick:function(){return!i&&void navigator.clipboard.writeText(o).then((function(){return c(!0)}))}})),i&&a.createElement(l.Snackbar,{className:"nelio-content-page-title__support-key-copied"},(0,F._x)("Copied!","text (support key)","nelio-content")))};function cn(e,n){var t,o,r=n.siteId,i=n.isSubscribed,c=document.getElementById(e);t=a.createElement(l.SlotFillProvider,null,a.createElement(rn,{isSubscribed:i,siteId:r}),a.createElement(re,null,a.createElement(on,null)),a.createElement(l.Popover.Slot,null)),(o=c)&&(a.createRoot?(0,a.createRoot)(o).render(t):(0,a.render)(t,o))}},4396:(e,n)=>{"use strict";function t(e){return e}n.L8=void 0,n.L8=function(){return t}},6942:(e,n)=>{var t;!function(){"use strict";var o={}.hasOwnProperty;function r(){for(var e="",n=0;n<arguments.length;n++){var t=arguments[n];t&&(e=c(e,i(t)))}return e}function i(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return r.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var n="";for(var t in e)o.call(e,t)&&e[t]&&(n=c(n,t));return n}function c(e,n){return n?e?e+" "+n:e+n:e}e.exports?(r.default=r,e.exports=r):void 0===(t=function(){return r}.apply(n,[]))||(e.exports=t)}()}},t={};function o(e){var r=t[e];if(void 0!==r)return r.exports;var i=t[e]={exports:{}};return n[e](i,i.exports,o),i.exports}o.m=n,e=[],o.O=(n,t,r,i)=>{if(!t){var c=1/0;for(u=0;u<e.length;u++){t=e[u][0],r=e[u][1],i=e[u][2];for(var a=!0,l=0;l<t.length;l++)(!1&i||c>=i)&&Object.keys(o.O).every((e=>o.O[e](t[l])))?t.splice(l--,1):(a=!1,i<c&&(c=i));if(a){e.splice(u--,1);var s=r();void 0!==s&&(n=s)}}return n}i=i||0;for(var u=e.length;u>0&&e[u-1][2]>i;u--)e[u]=e[u-1];e[u]=[t,r,i]},o.n=e=>{var n=e&&e.__esModule?()=>e.default:()=>e;return o.d(n,{a:n}),n},o.d=(e,n)=>{for(var t in n)o.o(n,t)&&!o.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},o.o=(e,n)=>Object.prototype.hasOwnProperty.call(e,n),o.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e={4808:0,4501:0};o.O.j=n=>0===e[n];var n=(n,t)=>{var r,i,c=t[0],a=t[1],l=t[2],s=0;if(c.some((n=>0!==e[n]))){for(r in a)o.o(a,r)&&(o.m[r]=a[r]);if(l)var u=l(o)}for(n&&n(t);s<c.length;s++)i=c[s],o.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return o.O(u)},t=self.webpackChunkNelioContent=self.webpackChunkNelioContent||[];t.forEach(n.bind(null,0)),t.push=n.bind(null,t.push.bind(t))})();var r=o.O(void 0,[4501],(()=>o(4223)));r=o.O(r);var i=NelioContent="undefined"==typeof NelioContent?{}:NelioContent;for(var c in r)i[c]=r[c];r.__esModule&&Object.defineProperty(i,"__esModule",{value:!0})})();