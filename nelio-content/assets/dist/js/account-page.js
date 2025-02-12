(()=>{var e,n={4223:(e,n,t)=>{"use strict";t.r(n),t.d(n,{initPage:()=>rn});var o={};t.r(o),t.d(o,{getAccount:()=>g,getEditingLicense:()=>E,getInvoices:()=>b,getProducts:()=>y,getSiteId:()=>v,getSites:()=>x,isAgencySummary:()=>w,isDialogOpen:()=>_,isLocked:()=>h});var r={};t.r(r),t.d(r,{closeDialog:()=>P,enableAgencyFullView:()=>T,lock:()=>D,openDialog:()=>I,receiveAccount:()=>N,receiveInvoices:()=>O,receiveProducts:()=>k,receiveSites:()=>C,removeSite:()=>S,setEditingLicense:()=>L,unlock:()=>A});var i={};t.r(i),t.d(i,{applyLicense:()=>V,cancelSubscription:()=>M,createFreeSite:()=>U,reactivateSubscription:()=>Y,removeLicense:()=>G,upgradeSubscription:()=>K});var c={};t.r(c),t.d(c,{getAccount:()=>q,getInvoices:()=>z,getProducts:()=>J,getSites:()=>Q});const a=window.wp.element,l=window.wp.components,s=window.wp.data;function u(e,n){return(0,s.useSelect)(e,n)}const m=window.lodash;var p={info:{plan:"free",siteId:(0,t(4396).L8)()(""),limits:{maxAutomationGroups:0,maxProfiles:0,maxProfilesPerNetwork:0}},invoices:[],products:[],sites:[],meta:{dialog:void 0,editingLicense:"",lockReason:void 0,isAgencySummary:!1}},d=function(){return d=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},d.apply(this,arguments)};const f=window.NelioContent.utils;function v(e){return e.info.siteId}function _(e,n){return e.meta.dialog===n}function h(e,n){return(0,f.isEmpty)(n)?!(0,f.isEmpty)(e.meta.lockReason):n===e.meta.lockReason}function E(e){return e.meta.editingLicense}function g(e){return e.info}function b(e){return e.invoices}function y(e){return e.products}function x(e){return e.sites||[]}function w(e){return e.meta.isAgencySummary}function N(e){return{type:"RECEIVE_ACCOUNT",account:e}}function C(e){return{type:"RECEIVE_SITES",sites:(0,m.castArray)(e)}}function S(e){return{type:"REMOVE_SITE",siteId:e}}function O(e){return{type:"RECEIVE_INVOICES",invoices:(0,m.castArray)(e)}}function k(e){return{type:"RECEIVE_PRODUCTS",products:(0,m.castArray)(e)}}function I(e){return{type:"OPEN_DIALOG",dialogName:e}}function P(){return{type:"CLOSE_DIALOG"}}function D(e){return void 0===e&&(e="no-reason"),{type:"LOCK_PAGE",reason:e}}function A(){return{type:"UNLOCK_PAGE"}}function L(e){return{type:"SET_EDITING_LICENSE",license:e}}function T(){return{type:"ENABLE_AGENCY_FULL_VIEW"}}const R=window.wp.apiFetch,j=t.n(R)();var F=function(e,n,t,o){return new(t||(t=Promise))((function(r,i){function c(e){try{l(o.next(e))}catch(e){i(e)}}function a(e){try{l(o.throw(e))}catch(e){i(e)}}function l(e){var n;e.done?r(e.value):(n=e.value,n instanceof t?n:new t((function(e){e(n)}))).then(c,a)}l((o=o.apply(e,n||[])).next())}))},B=function(e,n){var t,o,r,i={label:0,sent:function(){if(1&r[0])throw r[1];return r[1]},trys:[],ops:[]},c=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return c.next=a(0),c.throw=a(1),c.return=a(2),"function"==typeof Symbol&&(c[Symbol.iterator]=function(){return this}),c;function a(a){return function(l){return function(a){if(t)throw new TypeError("Generator is already executing.");for(;c&&(c=0,a[0]&&(i=0)),i;)try{if(t=1,o&&(r=2&a[0]?o.return:a[0]?o.throw||((r=o.return)&&r.call(o),0):o.next)&&!(r=r.call(o,a[1])).done)return r;switch(o=0,r&&(a=[2&a[0],r.value]),a[0]){case 0:case 1:r=a;break;case 4:return i.label++,{value:a[1],done:!1};case 5:i.label++,o=a[1],a=[0];continue;case 7:a=i.ops.pop(),i.trys.pop();continue;default:if(!((r=(r=i.trys).length>0&&r[r.length-1])||6!==a[0]&&2!==a[0])){i=0;continue}if(3===a[0]&&(!r||a[1]>r[0]&&a[1]<r[3])){i.label=a[1];break}if(6===a[0]&&i.label<r[1]){i.label=r[1],r=a;break}if(r&&i.label<r[2]){i.label=r[2],i.ops.push(a);break}r[2]&&i.ops.pop(),i.trys.pop();continue}a=n.call(e,i)}catch(e){a=[6,e],o=0}finally{t=r=0}if(5&a[0])throw a[1];return{value:a[0]?a[1]:void 0,done:!0}}([a,l])}}};function U(){return F(this,void 0,Promise,(function(){var e;return B(this,(function(n){switch(n.label){case 0:return n.trys.push([0,2,,4]),[4,j({path:"/nelio-content/v1/site/free",method:"POST"})];case 1:case 3:return n.sent(),[3,4];case 2:return e=n.sent(),[4,(0,f.showErrorNotice)(e)];case 4:return[2]}}))}))}function V(e){return F(this,void 0,Promise,(function(){var n;return B(this,(function(t){switch(t.label){case 0:return t.trys.push([0,2,,6]),(0,s.dispatch)(ee).lock("apply-license"),[4,j({path:"/nelio-content/v1/site/use-license",method:"POST",data:{license:e}})];case 1:return t.sent(),window.location.reload(),[3,6];case 2:return n=t.sent(),[4,(0,f.showErrorNotice)(n)];case 3:return t.sent(),[4,(0,s.dispatch)(ee).unlock()];case 4:return t.sent(),[4,(0,s.dispatch)(ee).closeDialog()];case 5:return t.sent(),[3,6];case 6:return[2]}}))}))}function G(e){return F(this,void 0,Promise,(function(){var n;return B(this,(function(t){switch(t.label){case 0:return t.trys.push([0,4,,8]),[4,(0,s.dispatch)(ee).lock("remove-license")];case 1:return t.sent(),[4,j({path:"/nelio-content/v1/site/remove-license",method:"POST",data:{siteId:e}})];case 2:return t.sent(),(0,s.select)(ee).getSiteId()===e?(window.location.reload(),[2]):[4,(0,s.dispatch)(ee).removeSite(e)];case 3:case 7:return t.sent(),[3,8];case 4:return n=t.sent(),[4,(0,f.showErrorNotice)(n)];case 5:return t.sent(),[4,(0,s.dispatch)(ee).unlock()];case 6:return t.sent(),[4,(0,s.dispatch)(ee).closeDialog()];case 8:return[2]}}))}))}function M(){return F(this,void 0,Promise,(function(){var e;return B(this,(function(n){switch(n.label){case 0:return n.trys.push([0,3,,7]),[4,(0,s.dispatch)(ee).lock("cancel-subscription")];case 1:return n.sent(),[4,j({path:"/nelio-content/v1/subscription",method:"DELETE"})];case 2:return n.sent(),window.location.reload(),[3,7];case 3:return e=n.sent(),[4,(0,f.showErrorNotice)(e)];case 4:return n.sent(),[4,(0,s.dispatch)(ee).unlock()];case 5:return n.sent(),[4,(0,s.dispatch)(ee).closeDialog()];case 6:return n.sent(),[3,7];case 7:return[2]}}))}))}function Y(){return F(this,void 0,Promise,(function(){var e;return B(this,(function(n){switch(n.label){case 0:return n.trys.push([0,3,,7]),[4,(0,s.dispatch)(ee).lock("reactivate-subscription")];case 1:return n.sent(),[4,j({path:"/nelio-content/v1/subscription/uncancel",method:"POST"})];case 2:return n.sent(),window.location.reload(),[3,7];case 3:return e=n.sent(),[4,(0,f.showErrorNotice)(e)];case 4:return n.sent(),[4,(0,s.dispatch)(ee).unlock()];case 5:return n.sent(),[4,(0,s.dispatch)(ee).closeDialog()];case 6:return n.sent(),[3,7];case 7:return[2]}}))}))}function K(e){return F(this,void 0,Promise,(function(){var n;return B(this,(function(t){switch(t.label){case 0:return t.trys.push([0,3,,6]),[4,(0,s.dispatch)(ee).lock("upgrade-subscription")];case 1:return t.sent(),[4,j({path:"/nelio-content/v1/subscription/upgrade",method:"PUT",data:{product:e}})];case 2:return t.sent(),window.location.reload(),[3,6];case 3:return n=t.sent(),[4,(0,s.dispatch)(ee).unlock()];case 4:return t.sent(),[4,(0,f.showErrorNotice)(n)];case 5:return t.sent(),[3,6];case 6:return[2]}}))}))}const $=window.wp.i18n;var H=function(e,n,t,o){return new(t||(t=Promise))((function(r,i){function c(e){try{l(o.next(e))}catch(e){i(e)}}function a(e){try{l(o.throw(e))}catch(e){i(e)}}function l(e){var n;e.done?r(e.value):(n=e.value,n instanceof t?n:new t((function(e){e(n)}))).then(c,a)}l((o=o.apply(e,n||[])).next())}))},W=function(e,n){var t,o,r,i={label:0,sent:function(){if(1&r[0])throw r[1];return r[1]},trys:[],ops:[]},c=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return c.next=a(0),c.throw=a(1),c.return=a(2),"function"==typeof Symbol&&(c[Symbol.iterator]=function(){return this}),c;function a(a){return function(l){return function(a){if(t)throw new TypeError("Generator is already executing.");for(;c&&(c=0,a[0]&&(i=0)),i;)try{if(t=1,o&&(r=2&a[0]?o.return:a[0]?o.throw||((r=o.return)&&r.call(o),0):o.next)&&!(r=r.call(o,a[1])).done)return r;switch(o=0,r&&(a=[2&a[0],r.value]),a[0]){case 0:case 1:r=a;break;case 4:return i.label++,{value:a[1],done:!1};case 5:i.label++,o=a[1],a=[0];continue;case 7:a=i.ops.pop(),i.trys.pop();continue;default:if(!((r=(r=i.trys).length>0&&r[r.length-1])||6!==a[0]&&2!==a[0])){i=0;continue}if(3===a[0]&&(!r||a[1]>r[0]&&a[1]<r[3])){i.label=a[1];break}if(6===a[0]&&i.label<r[1]){i.label=r[1],r=a;break}if(r&&i.label<r[2]){i.label=r[2],i.ops.push(a);break}r[2]&&i.ops.pop(),i.trys.pop();continue}a=n.call(e,i)}catch(e){a=[6,e],o=0}finally{t=r=0}if(5&a[0])throw a[1];return{value:a[0]?a[1]:void 0,done:!0}}([a,l])}}};function q(){return H(this,void 0,Promise,(function(){var e;return W(this,(function(n){switch(n.label){case 0:return[4,j({path:"/nelio-content/v1/site"})];case 1:return e=n.sent(),[4,(0,s.dispatch)(ee).receiveAccount(e)];case 2:return n.sent(),[2]}}))}))}function z(){return H(this,void 0,Promise,(function(){var e,n;return W(this,(function(t){switch(t.label){case 0:return t.trys.push([0,3,,6]),[4,j({path:"/nelio-content/v1/subscription/invoices"})];case 1:return e=t.sent(),[4,(0,s.dispatch)(ee).receiveInvoices(e)];case 2:case 5:return t.sent(),[3,6];case 3:return n=t.sent(),[4,(0,f.showErrorNotice)(n,(0,$._x)("Error while retrieving invoices","text","nelio-content"))];case 4:return t.sent(),[4,(0,s.dispatch)(ee).receiveInvoices([])];case 6:return[2]}}))}))}function Q(){return H(this,void 0,Promise,(function(){var e,n;return W(this,(function(t){switch(t.label){case 0:return t.trys.push([0,3,,6]),[4,j({path:"/nelio-content/v1/subscription/sites"})];case 1:return e=t.sent(),[4,(0,s.dispatch)(ee).receiveSites(e)];case 2:case 5:return t.sent(),[3,6];case 3:return n=t.sent(),[4,(0,f.showErrorNotice)(n,(0,$._x)("Error while retrieving sites","text","nelio-content"))];case 4:return t.sent(),[4,(0,s.dispatch)(ee).receiveSites([])];case 6:return[2]}}))}))}function J(){return H(this,void 0,Promise,(function(){var e,n;return W(this,(function(t){switch(t.label){case 0:return t.trys.push([0,3,,6]),[4,j({path:"/nelio-content/v1/products"})];case 1:return e=t.sent(),[4,(0,s.dispatch)(ee).receiveProducts(e)];case 2:case 5:return t.sent(),[3,6];case 3:return n=t.sent(),[4,(0,f.showErrorNotice)(n,(0,$._x)("Error while retrieving plans","text","nelio-content"))];case 4:return t.sent(),[4,(0,s.dispatch)(ee).receiveProducts([])];case 6:return[2]}}))}))}var X=function(){return X=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},X.apply(this,arguments)},Z=X(X({},r),i),ee=(0,s.createReduxStore)("nelio-content/account",{reducer:function(e,n){var t;return void 0===e&&(e=p),null!==(t=function(e,n){switch(n.type){case"RECEIVE_ACCOUNT":return d(d({},e),{info:n.account,meta:d(d({},e.meta),{isAgencySummary:"free"!==n.account.plan&&!!n.account.isAgency})});case"RECEIVE_PRODUCTS":return d(d({},e),{products:n.products});case"RECEIVE_SITES":return d(d({},e),{sites:n.sites});case"REMOVE_SITE":return d(d({},e),{sites:(0,m.reject)(e.sites,{id:n.siteId})});case"RECEIVE_INVOICES":return d(d({},e),{invoices:n.invoices});case"OPEN_DIALOG":return d(d({},e),{meta:d(d({},e.meta),{dialog:n.dialogName})});case"CLOSE_DIALOG":return d(d({},e),{meta:d(d({},e.meta),{dialog:void 0})});case"ENABLE_AGENCY_FULL_VIEW":return d(d({},e),{meta:d(d({},e.meta),{isAgencySummary:!1})});case"LOCK_PAGE":return d(d({},e),{meta:d(d({},e.meta),{lockReason:n.reason})});case"UNLOCK_PAGE":return d(d({},e),{meta:d(d({},e.meta),{lockReason:void 0})});case"SET_EDITING_LICENSE":return d(d({},e),{meta:d(d({},e.meta),{editingLicense:n.license})})}}(e,n))&&void 0!==t?t:e},controls:s.controls,actions:Z,selectors:o,resolvers:c});!function(e){(0,s.register)(e)}(ee);const ne=window.wp.notices,te=window.NelioContent.components;var oe=function(e){var n=e.children,t=re(),o=ie(),r=o.notices,i=o.removeNotice;return t?a.createElement(te.LoadingAnimation,{text:(0,$._x)("Loading…","text","nelio-content")}):a.createElement(a.Fragment,null,a.createElement(l.NoticeList,{notices:r,className:"components-editor-notices__pinned",onRemove:i}),n)},re=function(){return u((function(e){return e(ee).getAccount(),!e(ee).hasFinishedResolution("getAccount")}))},ie=function(){return{notices:u((function(e){return e(ne.store).getNotices()})),removeNotice:(0,s.useDispatch)(ne.store).removeNotice}},ce=t(6942),ae=t.n(ce);const le=window.NelioContent.date;var se=(0,$._x)("Y-m-d","text (date)","nelio-content"),ue=function(){var e=u((function(e){return e(ee).getAccount()}));if("free"===e.plan)return null;var n=e.mode,t=e.deactivationDate,o=e.nextChargeDate,r=e.nextChargeTotal,i=e.state;return"invitation"===n?a.createElement("div",{className:"nelio-content-plan__renewal"},(0,$._x)("You’re currently using a Free Pass to Nelio Content’s Premium Features. Enjoy the plugin and, please, help us improve it with your feedback!","text","nelio-content")):"canceled"===i?a.createElement("div",{className:"nelio-content-plan__renewal"},(0,a.createInterpolateElement)((0,$.sprintf)(/* translators: a date */ /* translators: a date */
(0,$._x)("Your subscription will end on %s.","text","nelio-content"),"<date>".concat((0,le.dateI18n)(se,t),"</date>")),{date:a.createElement("span",{className:"nelio-content-plan__renewal-date"})})):a.createElement("div",{className:"nelio-content-plan__renewal"},(0,a.createInterpolateElement)((0,$.sprintf)(/* translators: 1 -> price and currency; 2 -> date */ /* translators: 1 -> price and currency; 2 -> date */
(0,$._x)("Next charge will be %1$s on %2$s.","text","nelio-content"),"<money>".concat(r,"</money>"),"<date>".concat((0,le.dateI18n)(se,o),"</date>")),{date:a.createElement("span",{className:"nelio-content-plan__renewal-date"}),money:a.createElement("span",{className:"nelio-content-plan__renewal-amount"})}))},me=function(e){var n,t=e.plan,o=e.period,r=e.isCanceled,i=e.isInvitation;return a.createElement("h3",{className:"nelio-content-plan__title"},null!==(n=pe(t))&&void 0!==n?n:pe("basic"),a.createElement("span",{className:"nelio-content-plan__period"},i&&(0,$._x)("Invitation","text","nelio-content"),!i&&"month"===o&&(0,$._x)("Monthly","text","nelio-content"),!i&&"year"===o&&(0,$._x)("Yearly","text","nelio-content")),r&&a.createElement("span",{className:"nelio-content-plan__state-canceled"},(0,$._x)("Canceled","text (account state)","nelio-content")))};function pe(e){switch(e){case"free":return(0,$._x)("Nelio Content (Free)","text","nelio-content");case"plus":return(0,$._x)("Nelio Content Plus","text","nelio-content");case"standard":return(0,$._x)("Nelio Content Standard","text","nelio-content");case"basic":return(0,$._x)("Nelio Content Basic","text","nelio-content")}}var de=function(e){var n=u((function(n){return n(ee).isDialogOpen(e)})),t=(0,s.useDispatch)(ee),o=t.openDialog,r=t.closeDialog;return[n,function(n){return n?o(e):r()}]},fe=function(e){return u((function(n){return n(ee).isLocked(e)}))},ve=function(){return u((function(e){var n=(0,e(ee).getAccount)();if("free"===n.plan)return[];var t=e(ee).getProducts;return(0,m.filter)(t(),(function(e){return e.upgradeableFrom.includes(n.productId)}))}))},_e=function(e){var n=e.isOpen,t=e.onFocusOutside,o=e.placement,r=he(),i=r[0],c=r[1],u=fe(),m=fe("apply-license"),p=(0,s.useDispatch)(ee).applyLicense;return n?a.createElement(l.Popover,{placement:o,onFocusOutside:!u&&t?t:void 0},a.createElement("div",{className:"nelio-content-license-form"},a.createElement(l.TextControl,{value:i,placeholder:(0,$._x)("Type your license here","user","nelio-content"),maxLength:Math.max(21,26),className:"nelio-content-license-form__text-control",disabled:u,onChange:c}),a.createElement(l.Button,{variant:"primary",isBusy:m,className:"nelio-content-license-form__button",disabled:u||21!==i.length&&26!==i.length,onClick:function(){return p(i)}},m?(0,$._x)("Applying…","text","nelio-content"):(0,$._x)("Apply","command","nelio-content")))):null},he=function(){return[u((function(e){return e(ee).getEditingLicense()})),(0,s.useDispatch)(ee).setEditingLicense]},Ee=function(){return Ee=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},Ee.apply(this,arguments)},ge=function(e){var n=e.label,t=function(e,n){var t={};for(var o in e)Object.prototype.hasOwnProperty.call(e,o)&&n.indexOf(o)<0&&(t[o]=e[o]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var r=0;for(o=Object.getOwnPropertySymbols(e);r<o.length;r++)n.indexOf(o[r])<0&&Object.prototype.propertyIsEnumerable.call(e,o[r])&&(t[o[r]]=e[o[r]])}return t}(e,["label"]),o=de("license-popover"),r=o[0],i=o[1],c=fe();return a.createElement("span",null,a.createElement(l.Button,Ee({disabled:c,onClick:function(){return i(!0)}},t),n),a.createElement(_e,{placement:"bottom",onFocusOutside:function(){return i(!1)},isOpen:r}))},be=(0,$._x)("Y-m-d","text (date)","nelio-content"),ye=function(){var e=de("cancel-subscription"),n=e[0],t=e[1],o=fe("cancel-subscription"),r=fe(),i=u((function(e){var n=e(ee).getAccount();return"free"===n.plan?"":n.nextChargeDate})),c=(0,s.useDispatch)(ee).cancelSubscription;return a.createElement(a.Fragment,null,a.createElement(l.Button,{variant:"tertiary",isDestructive:!0,onClick:function(){return t(!0)},disabled:r},(0,$._x)("Cancel Subscription","command","nelio-content")),a.createElement(te.ConfirmationDialog,{title:(0,$._x)("Cancel Subscription?","text","nelio-content"),text:(0,$.sprintf)(/* translators: a date */ /* translators: a date */
(0,$._x)("Canceling your subscription will cause it not to renew. If you cancel your subscrition, it will continue until %s. Then, the subscription will expire and will not be invoiced again. Do you want to cancel your subscription?","user","nelio-content"),(0,le.dateI18n)(be,i)),confirmLabel:o?(0,$._x)("Canceling…","text","nelio-content"):(0,$._x)("Cancel Subscription","command","nelio-content"),cancelLabel:(0,$._x)("Back","command","nelio-content"),isDestructive:!0,onCancel:function(){return t(!1)},onConfirm:c,isConfirmEnabled:!r,isCancelEnabled:!r,isOpen:n,isConfirmBusy:o}))},xe=(0,$._x)("Y-m-d","text (date)","nelio-content"),we=function(){var e=u((function(e){return e(ee).isLocked()})),n=u((function(e){return e(ee).isDialogOpen("reactivate-subscription")})),t=u((function(e){return e(ee).isLocked("reactivate-subscription")})),o=u((function(e){var n=e(ee).getAccount();return"free"===n.plan?"":n.nextChargeDate})),r=(0,s.useDispatch)(ee),i=r.reactivateSubscription,c=r.openDialog,m=r.closeDialog;return a.createElement(a.Fragment,null,a.createElement(l.Button,{variant:"primary",onClick:function(){return c("reactivate-subscription")},disabled:e},(0,$._x)("Reactivate Subscription","command","nelio-content")),a.createElement(te.ConfirmationDialog,{title:(0,$._x)("Reactivate Subscription?","text","nelio-content"),text:(0,$.sprintf)(/* translators: a date */ /* translators: a date */
(0,$._x)("Reactivating your subscription will cause it to renew on %s. Do you want to reactivate your subscription?","user","nelio-content"),(0,le.dateI18n)(xe,o)),confirmLabel:t?(0,$._x)("Reactivating…","text","nelio-content"):(0,$._x)("Reactivate Subscription","command","nelio-content"),cancelLabel:(0,$._x)("Back","command","nelio-content"),onCancel:m,onConfirm:i,isConfirmEnabled:!e,isCancelEnabled:!e,isOpen:n,isConfirmBusy:t}))},Ne=function(){return Ne=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},Ne.apply(this,arguments)},Ce=function(e){var n=e.isOpen,t=e.placement,o=e.onUpgrade,r=e.onFocusOutside,i=(0,a.useState)(),c=i[0],u=i[1],m=ve(),p=ke(),d=(0,s.useDispatch)(ee).upgradeSubscription;return n?p?a.createElement(l.Popover,{className:"nelio-content-upgrade-form--loading",noArrow:!1,placement:t,onFocusOutside:r},a.createElement(l.Spinner,null)):a.createElement(l.Popover,{className:"nelio-content-upgrade-form",noArrow:!1,placement:t,onFocusOutside:r},a.createElement(l.RadioControl,{className:"nelio-content-upgrade-form__product-container",label:(0,$._x)("Subscription Plans","text","nelio-content"),selected:c,options:m.map((function(e){return{label:a.createElement(Se,Ne({},e)),value:e.id}})),onChange:u}),a.createElement("div",{className:"nelio-content-upgrade-form__button-container"},a.createElement(l.Button,{variant:"primary",className:"nelio-content-upgrade-form__button",disabled:!c,onClick:function(){c&&(o(),d(c))}},(0,$._x)("Upgrade","command","nelio-content")))):null},Se=function(e){var n=e.displayName,t=e.price,o=e.description,r=Oe();return a.createElement("div",{className:"nelio-content-upgrade-form__product"},a.createElement("strong",{className:"nelio-content-upgrade-form__product-name"},Pe(n)),a.createElement("span",{className:"nelio-content-upgrade-form__product-price",title:r},Ie(t[r],r)),a.createElement("span",{className:"nelio-content-upgrade-form__product-description"},Pe(o)))},Oe=function(){return u((function(e){var n=e(ee).getAccount();return"free"===n.plan?"USD":n.currency||"USD"}))},ke=function(){return u((function(e){return!e(ee).hasFinishedResolution("getProducts")}))},Ie=function(e,n){return"EUR"===n?"".concat(null!=e?e:"—","€"):"$".concat(null!=e?e:"—")},Pe=function(e){return e[(0,f.getShortLocale)()]||e.en},De=function(){var e=(0,a.useState)(!1),n=e[0],t=e[1],o=fe(),r=fe("upgrade-subscription");return ve().length?a.createElement("div",null,a.createElement(l.Button,{variant:"primary",isBusy:r,onClick:function(){return t(!0)},disabled:o},r?(0,$._x)("Upgrading…","text","nelio-content"):(0,$._x)("Upgrade Subscription","command","nelio-content")),a.createElement(Ce,{isOpen:n,placement:"bottom",onFocusOutside:function(){return t(!1)},onUpgrade:function(){return t(!1)}})):null},Ae=function(){return Ae=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},Ae.apply(this,arguments)},Le=function(e){var n=e.label,t=function(e,n){var t={};for(var o in e)Object.prototype.hasOwnProperty.call(e,o)&&n.indexOf(o)<0&&(t[o]=e[o]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var r=0;for(o=Object.getOwnPropertySymbols(e);r<o.length;r++)n.indexOf(o[r])<0&&Object.prototype.propertyIsEnumerable.call(e,o[r])&&(t[o[r]]=e[o[r]])}return t}(e,["label"]),o=de("remove-license"),r=o[0],i=o[1],c=u((function(e){return e(ee).getSiteId()})),m=fe("remove-license"),p=fe(),d=(0,s.useDispatch)(ee).removeLicense;return a.createElement(a.Fragment,null,a.createElement(l.Button,Ae({onClick:function(){return i(!0)},disabled:p},t),n),a.createElement(te.ConfirmationDialog,{title:(0,$._x)("Downgrade to Free Version?","text","nelio-content"),text:(0,$._x)("This action will remove the license from this site so that you can use it somewhere else. Nelio Content will remain active on this site, but you will be using the free version instead. This might result in some scheduled social messages being lost. Do you want to continue?","user","nelio-content"),confirmLabel:m?(0,$._x)("Downgrading…","text (remove license)","nelio-content"):(0,$._x)("Downgrade","command (remove license)","nelio-content"),cancelLabel:(0,$._x)("Back","command","nelio-content"),isDestructive:!0,onCancel:function(){return i(!1)},onConfirm:function(){d(c)},isConfirmEnabled:!p,isCancelEnabled:!p,isOpen:r,isConfirmBusy:m}))};window.NelioContent.data;const Te=window.wp.url;var Re=function(){return a.createElement(l.ExternalLink,{className:"components-button is-primary",href:(0,Te.addQueryArgs)("https://twitter.com/intent/tweet",{text:(0,$._x)("Nelio Content is an awesome #EditorialCalendar for #WordPress by @NelioSoft!","text","nelio-content")})},(0,$._x)("Tweet About Nelio Content","command","nelio-content"))},je=function(){var e=u((function(e){return e(ee).getAccount()}));if("free"===e.plan)return null;var n=e.mode,t=e.state,o=e.plan,r=e.period;return a.createElement("div",{className:"nelio-content-account-container__box nelio-content-plan"},a.createElement("div",{className:"nelio-content-plan__content"},a.createElement(me,{isCanceled:"canceled"===t,isInvitation:"invitation"===n,plan:o,period:r}),a.createElement(ue,null)),a.createElement("div",{className:"nelio-content-plan__actions"},"invitation"===n&&a.createElement(Re,null),"invitation"!==n&&"active"===t&&a.createElement(a.Fragment,null,a.createElement(ye,null),a.createElement(De,null)),"invitation"!==n&&"canceled"===t&&a.createElement(we,null)))},Fe=function(){var e=u((function(e){return e(ee).getAccount()}));if("free"===e.plan)return null;var n=e.plan,t=e.period,o=e.state,r=e.mode;return a.createElement("div",{className:"nelio-content-account-container__box nelio-content-plan"},a.createElement("div",{className:"nelio-content-plan__content"},a.createElement(me,{isCanceled:"canceled"===o,isInvitation:"invitation"===r,plan:n,period:t}),a.createElement("div",{className:"nelio-content-plan__renewal"},(0,$._x)("You’re currently using an agency subscription plan.","user","nelio-content"))),a.createElement("div",{className:"nelio-content-plan__actions"},a.createElement(Be,null),a.createElement(Ue,null)))},Be=function(){var e=Ve(),n=(0,a.useState)(!1),t=n[0],o=n[1],r=(0,s.useDispatch)(ee).removeLicense,i=Ge(),c=function(){return o(!1)};return a.createElement(a.Fragment,null,a.createElement(l.Button,{isDestructive:!0,className:"nelio-content-plan__action",onClick:function(){return o(!0)},disabled:i},(0,$._x)("Downgrade to Free Version","command","nelio-content")),a.createElement(te.ConfirmationDialog,{title:(0,$._x)("Downgrade to Free version?","text","nelio-content"),text:(0,$._x)("This will remove the subscription license from the site and you’ll be using Nelio Content’s free version.","text","nelio-content"),confirmLabel:(0,$._x)("Downgrade","command","nelio-content"),isOpen:t,onCancel:c,onConfirm:function(){c(),r(e)}}))},Ue=function(){var e=u((function(e){return e(ee).getAccount()})),n=Ge(),t=(0,a.useState)(!1),o=t[0],r=t[1],i=(0,a.useState)(""),c=i[0],m=i[1],p=(0,s.useDispatch)(ee).enableAgencyFullView;if("free"===e.plan)return null;var d=function(){return r(!1)};return a.createElement("div",null,a.createElement(l.Button,{variant:"secondary",className:"nelio-content-plan__action",onClick:function(){return r(!0)},disabled:n},(0,$._x)("View Details","command","nelio-content")),o&&a.createElement(l.Popover,{noArrow:!1,placement:"bottom-start",onFocusOutside:d},a.createElement("div",{className:"nelio-content-license-form"},a.createElement(l.TextControl,{value:c,placeholder:(0,$._x)("Type your license here","user","nelio-content"),className:"nelio-content-license-form__text-control",onChange:m}),a.createElement(l.Button,{variant:"primary",className:"nelio-content-license-form__button",onClick:function(){e.license&&c===e.license?(p(),d()):m("")},disabled:c.length!==e.license.length},(0,$._x)("Validate","command","nelio-content")))))},Ve=function(){return u((function(e){return e(ee).getSiteId()}))},Ge=function(){return u((function(e){return e(ee).isLocked()}))},Me=(0,$._x)("Y-m-d","text (date)","nelio-content"),Ye=function(){var e=u((function(e){return e(ee).getAccount()}));if("free"===e.plan)return null;var n=e.creationDate,t=e.firstname,o=e.lastname,r=e.license,i=e.email,c=e.photo,s=(0,$.sprintf)(/* translators: 1 -> first name, 2 -> lastname */ /* translators: 1 -> first name, 2 -> lastname */
(0,$._x)("%1$s %2$s","text (full name)","nelio-content"),t,o),m=(0,f.getFirstLatinizedLetter)(s)||"a";return a.createElement("div",{className:"nelio-content-account-container__box nelio-content-info"},a.createElement("h3",{className:"nelio-content-info__title"},(0,$._x)("Account Information","title (account)","nelio-content")),a.createElement("div",{className:"nelio-content-info__container"},a.createElement("div",{className:"nelio-content-info__profile"},a.createElement("div",{className:"nelio-content-info__picture nelio-content-first-letter-".concat(m)},a.createElement("div",{className:"nelio-content-info__actual-picture",style:{backgroundImage:"url(".concat(c,")")}}))),a.createElement("div",{className:"nelio-content-info__details"},a.createElement("p",{className:"nelio-content-info__name"},s),a.createElement("p",{className:"nelio-content-info__email"},a.createElement(l.Dashicon,{icon:"email",className:"nelio-content-info__icon"}),i),a.createElement("p",{className:"nelio-content-info__creation-date"},a.createElement(l.Dashicon,{icon:"calendar",className:"nelio-content-info__icon"}),(0,a.createInterpolateElement)((0,$.sprintf)(/* translators: a date */ /* translators: a date */
(0,$._x)("Member since %s.","text","nelio-content"),"<date>".concat((0,le.dateI18n)(Me,n),"</date>")),{date:a.createElement("strong",null)})),a.createElement("div",{className:"nelio-content-info__license"},a.createElement(l.Dashicon,{icon:"admin-network",className:"nelio-content-info__icon"}),a.createElement("code",{title:(0,$._x)("License Key","text","nelio-content")},r),a.createElement("div",{className:"nelio-content-info__change-license"},a.createElement(ge,{variant:"link",label:(0,$._x)("Change","command","nelio-content")})),a.createElement("div",{className:"nelio-content-info__remove-license"},a.createElement(Le,{variant:"link",isDestructive:!0,label:(0,$._x)("Remove","command","nelio-content")}))))))},Ke=function(e){var n=e.invoiceUrl,t=e.reference,o=e.chargeDate,r=e.isRefunded,i=e.subtotalDisplay;return a.createElement("tr",{className:"nelio-content-invoice"},a.createElement("td",{className:"nelio-content-invoice__reference"},a.createElement("a",{href:n,className:"nelio-content-invoice__link",target:"_blank",rel:"noopener noreferrer"},t)),a.createElement("td",{className:"nelio-content-invoice__date"},o),a.createElement("td",{className:"nelio-content-invoice__total"},r&&a.createElement("span",{className:"nelio-content-invoice__label"},(0,$._x)("(Refunded)","text (invoice)","nelio-content")),a.createElement("span",{className:ae()({"nelio-content-invoice__total-value":!0,"nelio-content-invoice__total-value--refunded":r})},i)))},$e=function(){return $e=Object.assign||function(e){for(var n,t=1,o=arguments.length;t<o;t++)for(var r in n=arguments[t])Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r]);return e},$e.apply(this,arguments)},He=function(){var e=We(),n=qe(),t=ze(),o=Qe();return t?n?a.createElement("div",{className:"nelio-content-account-container__box nelio-content-billing"},a.createElement("h3",{className:"nelio-content-billing__title"},(0,$._x)("Billing History","text","nelio-content"),n&&a.createElement(l.Spinner,null))):a.createElement("div",{className:"nelio-content-account-container__box nelio-content-billing"},a.createElement("h3",{className:"nelio-content-billing__title"},(0,$._x)("Billing History","text","nelio-content")),!!o&&a.createElement(l.ExternalLink,{className:"nelio-content-billing__action",href:o},(0,$._x)("Manage Payments","command","nelio-content")),a.createElement("table",{className:"nelio-content-billing__container"},a.createElement("thead",null,a.createElement("tr",null,a.createElement("th",{className:"nelio-content-billing__reference"},(0,$._x)("Invoice Reference","text (account, billing table title)","nelio-content")),a.createElement("th",{className:"nelio-content-billing__date"},(0,$._x)("Date","text (account, billing table title)","nelio-content")),a.createElement("th",{className:"nelio-content-billing__total"},(0,$._x)("Total","text (account, billing table title)","nelio-content")))),a.createElement("tbody",{className:"invoice-list"},e.map((function(e){return a.createElement(Ke,$e({key:e.reference},e))}))))):null},We=function(){return u((function(e){return e(ee).getInvoices()||[]}))},qe=function(){return u((function(e){return!e(ee).hasFinishedResolution("getInvoices")}))},ze=function(){return u((function(e){var n=e(ee).getAccount();return"free"!==n.plan&&"regular"===n.mode}))},Qe=function(){return u((function(e){var n=e(ee).getAccount();return"free"===n.plan?"":n.urlToManagePayments}))},Je=function(e){var n=e.site,t=(0,a.useState)(!1),o=t[0],r=t[1],i=(0,s.useDispatch)(ee).removeLicense,c=u((function(e){return e(ee).isLocked()})),m=n.isCurrentSite,p=n.url,d=n.actualUrl;return a.createElement("li",{className:"nelio-content-site"},m?a.createElement("span",{className:"nelio-content-site__url"},a.createElement("span",{className:"nelio-content-site__current-site"},d,!!d&&p!==d&&a.createElement(te.HelpIcon,{className:"nelio-content-site__current-site-help",type:"info",text:(0,$.sprintf)(/* translators: site URL */ /* translators: site URL */
(0,$._x)("Activation URL is “%s”","text","nelio-content"),p)})),a.createElement("span",{className:"nelio-content-site__label"},(0,$._x)("This site","text","nelio-content"))):a.createElement(a.Fragment,null,a.createElement("span",{className:"nelio-content-site__url"},a.createElement("a",{href:p,className:ae()("nelio-content-site__link",{"nelio-content-site__link--current":m}),target:"_blank",rel:"noopener noreferrer"},p)),a.createElement(l.Button,{isDestructive:!0,variant:"tertiary",className:"nelio-content-site__unlink-button",onClick:function(){return r(!0)},disabled:c},(0,$._x)("Unlink Site","command","nelio-content")),a.createElement(te.ConfirmationDialog,{title:(0,$._x)("Unlink Site?","text","nelio-content"),text:(0,$._x)("This will remove the subscription license from the site.","text","nelio-content"),confirmLabel:(0,$._x)("Unlink","command","nelio-content"),isOpen:o,onCancel:function(){return r(!1)},onConfirm:function(){r(!1),i(n.id)}})))},Xe=function(){var e=Ze(),n=nn(),t=en(),o=t.length;return n<=1?null:a.createElement("div",{className:"nelio-content-account-container__box nelio-content-sites"},a.createElement("h3",{className:"nelio-content-sites__title"},(0,$._x)("Sites","text","nelio-content"),e&&a.createElement(l.Spinner,null),a.createElement("span",{className:"nelio-content-sites__availability"},!e&&"".concat(o," / ").concat(n))),a.createElement("ul",{className:"nelio-content-sites__list"},!e&&t.map((function(e){return a.createElement(Je,{key:e.id,site:e})}))))},Ze=function(){return u((function(e){return!e(ee).hasFinishedResolution("getSites")}))},en=function(){return u((function(e){return e(ee).getSites()||[]}))},nn=function(){return u((function(e){var n=e(ee).getAccount();return"free"===n.plan?1:n.sitesAllowed}))},tn=function(){var e=u((function(e){return e(ee).getAccount()})),n=u((function(e){return e(ee).hasFinishedResolution("getAccount")})),t=u((function(e){return e(ee).isAgencySummary()}));if(!n)return null;if("free"===e.plan)return a.createElement("div",{className:"nelio-content-account-container nelio-content-account-container--free-user"},a.createElement(te.SubscriptionRequiredPage,{page:"account"}));if(t)return a.createElement("div",{className:"nelio-content-account-container nelio-content-account-container--is-agency-summary"},a.createElement(Fe,null));var o="invitation"===e.mode,r=1<e.sitesAllowed;return a.createElement("div",{className:ae()("nelio-content-account-container",{"nelio-content-account-container--is-invitation":o,"nelio-content-account-container--is-subscribed":!o,"nelio-content-account-container--is-multi-site":r})},a.createElement(je,null),a.createElement(Ye,null),a.createElement(Xe,null),a.createElement(He,null))},on=function(e){var n,t=e.isSubscribed,o=e.siteId,r=(0,a.useState)(!1),i=r[0],c=r[1];return(0,a.useEffect)((function(){var e=setTimeout((function(){return c(!1)}),1500);return function(){return clearTimeout(e)}}),[i]),a.createElement("h1",{className:"wp-heading-inline nelio-content-page-title"},a.createElement("span",null,t?(0,$._x)("Account Details","text","nelio-content"):(0,$._x)("Upgrade to Premium","user","nelio-content")),a.createElement("span",{className:"nelio-content-page-title__support-key"},a.createElement("strong",null,(0,$._x)("Support Key:","text","nelio-content")),a.createElement("code",null,o),!!(null===(n=navigator.clipboard)||void 0===n?void 0:n.writeText)&&a.createElement(l.Button,{icon:"admin-page",onClick:function(){return!i&&void navigator.clipboard.writeText(o).then((function(){return c(!0)}))}})),i&&a.createElement(l.Snackbar,{className:"nelio-content-page-title__support-key-copied"},(0,$._x)("Copied!","text (support key)","nelio-content")))};function rn(e,n){var t,o,r=n.siteId,i=n.isSubscribed,c=document.getElementById(e);t=a.createElement(l.SlotFillProvider,null,a.createElement(on,{isSubscribed:i,siteId:r}),a.createElement(oe,null,a.createElement(tn,null)),a.createElement(l.Popover.Slot,null)),(o=c)&&(a.createRoot?(0,a.createRoot)(o).render(t):(0,a.render)(t,o))}},4396:(e,n)=>{"use strict";function t(e){return e}n.L8=void 0,n.L8=function(){return t}},6942:(e,n)=>{var t;!function(){"use strict";var o={}.hasOwnProperty;function r(){for(var e="",n=0;n<arguments.length;n++){var t=arguments[n];t&&(e=c(e,i(t)))}return e}function i(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return r.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var n="";for(var t in e)o.call(e,t)&&e[t]&&(n=c(n,t));return n}function c(e,n){return n?e?e+" "+n:e+n:e}e.exports?(r.default=r,e.exports=r):void 0===(t=function(){return r}.apply(n,[]))||(e.exports=t)}()}},t={};function o(e){var r=t[e];if(void 0!==r)return r.exports;var i=t[e]={exports:{}};return n[e](i,i.exports,o),i.exports}o.m=n,e=[],o.O=(n,t,r,i)=>{if(!t){var c=1/0;for(u=0;u<e.length;u++){t=e[u][0],r=e[u][1],i=e[u][2];for(var a=!0,l=0;l<t.length;l++)(!1&i||c>=i)&&Object.keys(o.O).every((e=>o.O[e](t[l])))?t.splice(l--,1):(a=!1,i<c&&(c=i));if(a){e.splice(u--,1);var s=r();void 0!==s&&(n=s)}}return n}i=i||0;for(var u=e.length;u>0&&e[u-1][2]>i;u--)e[u]=e[u-1];e[u]=[t,r,i]},o.n=e=>{var n=e&&e.__esModule?()=>e.default:()=>e;return o.d(n,{a:n}),n},o.d=(e,n)=>{for(var t in n)o.o(n,t)&&!o.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},o.o=(e,n)=>Object.prototype.hasOwnProperty.call(e,n),o.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e={4808:0,4501:0};o.O.j=n=>0===e[n];var n=(n,t)=>{var r,i,c=t[0],a=t[1],l=t[2],s=0;if(c.some((n=>0!==e[n]))){for(r in a)o.o(a,r)&&(o.m[r]=a[r]);if(l)var u=l(o)}for(n&&n(t);s<c.length;s++)i=c[s],o.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return o.O(u)},t=self.webpackChunkNelioContent=self.webpackChunkNelioContent||[];t.forEach(n.bind(null,0)),t.push=n.bind(null,t.push.bind(t))})();var r=o.O(void 0,[4501],(()=>o(4223)));r=o.O(r);var i=NelioContent="undefined"==typeof NelioContent?{}:NelioContent;for(var c in r)i[c]=r[c];r.__esModule&&Object.defineProperty(i,"__esModule",{value:!0})})();