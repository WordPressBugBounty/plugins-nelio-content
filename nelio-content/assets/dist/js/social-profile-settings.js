(()=>{var e,t={9167:(e,t,n)=>{"use strict";n.r(t),n.d(t,{initPage:()=>ze});var o={};n.r(o),n.d(o,{areThereProfilesBeingDeleted:()=>C,getConnectionDialog:()=>w,getEditingEmail:()=>x,getEditingProfileId:()=>k,getEditingProfileSettings:()=>S,getEditingQueryArgs:()=>N,getKindDialog:()=>_,isProfileBeingDeleted:()=>A,isProfileListRefreshing:()=>O,isSavingProfileSettings:()=>P});var r={};n.r(r),n.d(r,{closeKindSelectorDialog:()=>R,closeProfileEditor:()=>L,markAsBeingDeleted:()=>j,markAsDeleted:()=>K,markAsRefreshtingProfiles:()=>M,markAsSavingProfileSettings:()=>F,openKindSelectorDialog:()=>D,openProfileEditor:()=>T,setEditingEmail:()=>B,setEditingQueryArgs:()=>G});var i={};n.r(i),n.d(i,{closeConnectionDialog:()=>X,closeConnectionDialogAndRefresh:()=>Y,deleteProfile:()=>se,openConnectionDialog:()=>z,refreshProfile:()=>W,refreshSocialProfiles:()=>ue,saveProfileSettings:()=>le});const l=window.wp.element;function a(e,t){t&&(l.createRoot?(0,l.createRoot)(t).render(e):(0,l.render)(e,t))}const c=window.wp.i18n,s=window.NelioContent.components,u=window.wp.data;function f(e,t){return(0,u.useSelect)(e,t)}var d={connectionDialog:void 0,kindDialog:void 0},p={profileId:void 0,isSaving:!1,settings:{email:"",permalinkQueryArgs:[]}},m={deleting:[],refreshing:[],isRefreshing:!1},g=function(){return g=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},g.apply(this,arguments)},h=function(){return h=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},h.apply(this,arguments)};const v=window.lodash;var E=function(){return E=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},E.apply(this,arguments)},y=function(e,t,n){if(n||2===arguments.length)for(var o,r=0,i=t.length;r<i;r++)!o&&r in t||(o||(o=Array.prototype.slice.call(t,0,r)),o[r]=t[r]);return e.concat(o||Array.prototype.slice.call(t))};const b=(0,u.combineReducers)({connection:function(e,t){var n;return void 0===e&&(e=d),null!==(n=function(e,t){switch(t.type){case"OPEN_KIND_DIALOG_FOR_NETWORK":return g(g({},e),{kindDialog:t.network});case"CLOSE_KIND_DIALOG":return g(g({},e),{kindDialog:void 0});case"OPEN_CONNECTION_DIALOG":return g(g({},e),{connectionDialog:t.dialog});case"CLOSE_CONNECTION_DIALOG":return g(g({},e),{connectionDialog:void 0})}}(e,t))&&void 0!==n?n:e},profileEditor:function(e,t){var n;return void 0===e&&(e=p),null!==(n=function(e,t){switch(t.type){case"OPEN_PROFILE_EDITOR":return{profileId:t.profileId,isSaving:!1,settings:t.settings};case"CLOSE_PROFILE_EDITOR":return h(h({},e),{profileId:void 0});case"MARK_AS_SAVING_PROFILE_SETTINGS":return h(h({},e),{isSaving:t.saving});case"SET_EDITING_EMAIL":return h(h({},e),{settings:h(h({},e.settings),{email:t.email})});case"SET_EDITING_QUERY_ARGS":return h(h({},e),{settings:h(h({},e.settings),{permalinkQueryArgs:t.args})})}}(e,t))&&void 0!==n?n:e},profileList:function(e,t){var n;return void 0===e&&(e=m),null!==(n=function(e,t){switch(t.type){case"MARK_AS_BEING_DELETED":return E(E({},e),{deleting:y(y([],e.deleting,!0),[t.profileId],!1)});case"MARK_AS_DELETED":return E(E({},e),{deleting:(0,v.without)(e.deleting,t.profileId)});case"MARK_AS_REFRESHING_PROFILES":return E(E({},e),{isRefreshing:t.isRefreshing})}}(e,t))&&void 0!==n?n:e}});function _(e){return e.connection.kindDialog}function w(e){return e.connection.connectionDialog}function k(e){return e.profileEditor.profileId}function S(e){return e.profileEditor.settings}function x(e){return e.profileEditor.settings.email}function N(e){return e.profileEditor.settings.permalinkQueryArgs}function P(e){return e.profileEditor.isSaving}const I=window.NelioContent.utils;function O(e){return!!e.profileList.isRefreshing}function C(e){return!(0,I.isEmpty)(e.profileList.deleting)}function A(e,t){return e.profileList.deleting.includes(t)}function D(e){return{type:"OPEN_KIND_DIALOG_FOR_NETWORK",network:e}}function R(){return{type:"CLOSE_KIND_DIALOG"}}function T(e,t){return{type:"OPEN_PROFILE_EDITOR",profileId:e,settings:t}}function L(){return{type:"CLOSE_PROFILE_EDITOR"}}function B(e){return{type:"SET_EDITING_EMAIL",email:e}}function G(e){return{type:"SET_EDITING_QUERY_ARGS",args:e}}function F(e){return{type:"MARK_AS_SAVING_PROFILE_SETTINGS",saving:e}}function j(e){return{type:"MARK_AS_BEING_DELETED",profileId:e}}function K(e){return{type:"MARK_AS_DELETED",profileId:e}}function M(e){return{type:"MARK_AS_REFRESHING_PROFILES",isRefreshing:e}}const Q=window.wp.url,q=window.NelioContent.data,H=window.NelioContent.networks;var U=function(e,t,n,o){return new(n||(n=Promise))((function(r,i){function l(e){try{c(o.next(e))}catch(e){i(e)}}function a(e){try{c(o.throw(e))}catch(e){i(e)}}function c(e){var t;e.done?r(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(l,a)}c((o=o.apply(e,t||[])).next())}))},V=function(e,t){var n,o,r,i={label:0,sent:function(){if(1&r[0])throw r[1];return r[1]},trys:[],ops:[]},l=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return l.next=a(0),l.throw=a(1),l.return=a(2),"function"==typeof Symbol&&(l[Symbol.iterator]=function(){return this}),l;function a(a){return function(c){return function(a){if(n)throw new TypeError("Generator is already executing.");for(;l&&(l=0,a[0]&&(i=0)),i;)try{if(n=1,o&&(r=2&a[0]?o.return:a[0]?o.throw||((r=o.return)&&r.call(o),0):o.next)&&!(r=r.call(o,a[1])).done)return r;switch(o=0,r&&(a=[2&a[0],r.value]),a[0]){case 0:case 1:r=a;break;case 4:return i.label++,{value:a[1],done:!1};case 5:i.label++,o=a[1],a=[0];continue;case 7:a=i.ops.pop(),i.trys.pop();continue;default:if(!((r=(r=i.trys).length>0&&r[r.length-1])||6!==a[0]&&2!==a[0])){i=0;continue}if(3===a[0]&&(!r||a[1]>r[0]&&a[1]<r[3])){i.label=a[1];break}if(6===a[0]&&i.label<r[1]){i.label=r[1],r=a;break}if(r&&i.label<r[2]){i.label=r[2],i.ops.push(a);break}r[2]&&i.ops.pop(),i.trys.pop();continue}a=t.call(e,i)}catch(e){a=[6,e],o=0}finally{n=r=0}if(5&a[0])throw a[1];return{value:a[0]?a[1]:void 0,done:!0}}([a,c])}}};function z(e,t){return U(this,void 0,Promise,(function(){var n,o;return V(this,(function(r){switch(r.label){case 0:return[4,ee()];case 1:return r.sent(),(0,u.select)(de).getConnectionDialog()?[2]:(n=(0,H.getDefaultPublicationValue)("buffer"===e||"hootsuite"===e?t:e),o=J(e,t),[2,$((0,Q.addQueryArgs)(o,{publicationFreq:n}))])}}))}))}function W(e){return U(this,void 0,Promise,(function(){var t,n,o,r;return V(this,(function(i){switch(i.label){case 0:return[4,ee()];case 1:return i.sent(),(0,u.select)(de).getConnectionDialog()?[2]:(t=(0,u.select)(q.store).getSocialProfile(e))?(n=t.network,o=t.kind,r=t.isBuffer||t.isHootsuite?J(t.isBuffer?"buffer":"hootsuite",n):J(n,o),[2,$((0,Q.addQueryArgs)(r,{socialProfileId:e}))]):[2]}}))}))}function Y(){return U(this,void 0,Promise,(function(){var e;return V(this,(function(t){switch(t.label){case 0:return[4,ee()];case 1:return t.sent(),(e=(0,u.select)(de).getConnectionDialog())?[4,(0,u.dispatch)(de).closeConnectionDialog(e)]:[2];case 2:return t.sent(),[4,(0,u.dispatch)(de).refreshSocialProfiles()];case 3:return t.sent(),[2]}}))}))}function X(e){return U(this,void 0,Promise,(function(){return V(this,(function(t){switch(t.label){case 0:return[4,ee()];case 1:return t.sent(),e.close||e.close(),[2,{type:"CLOSE_CONNECTION_DIALOG"}]}}))}))}function J(e,t){var n=(0,u.select)(q.store).getSiteId(),o=(0,u.select)(q.store).getApiRoot(),r=(0,u.select)(q.store).getCurrentUserId(),i=(0,u.select)(q.store).getSiteLanguage();if("buffer"===e)return(0,Q.addQueryArgs)("".concat(o,"/connect/buffer"),{network:t,siteId:n,creatorId:r,lang:i});if("hootsuite"===e)return(0,Q.addQueryArgs)("".concat(o,"/connect/hootsuite"),{network:t,siteId:n,creatorId:r,lang:i});var l=Z(e,t),a=l.network,c=l.kind,s=c&&"single"!==c?"".concat(a,"/").concat(c):a;return(0,Q.addQueryArgs)("".concat(o,"/connect/").concat(s),{siteId:n,creatorId:r,lang:i})}var Z=function(e,t){return"instagram"===e?{network:"facebook",kind:"instagram"}:{network:e,kind:t}};function $(e){var t=e.includes("telegram")?750:520,n=window.open(e,"","width=".concat(640,",height=").concat(t));if(n){var o=setInterval((function(){n.closed&&(clearInterval(o),(0,u.dispatch)(de).closeConnectionDialogAndRefresh())}),500);return{type:"OPEN_CONNECTION_DIALOG",dialog:n}}}function ee(){return new Promise((function(e){return e()}))}const te=window.wp.apiFetch,ne=n.n(te)();var oe=function(){return oe=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},oe.apply(this,arguments)},re=function(e,t,n,o){return new(n||(n=Promise))((function(r,i){function l(e){try{c(o.next(e))}catch(e){i(e)}}function a(e){try{c(o.throw(e))}catch(e){i(e)}}function c(e){var t;e.done?r(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(l,a)}c((o=o.apply(e,t||[])).next())}))},ie=function(e,t){var n,o,r,i={label:0,sent:function(){if(1&r[0])throw r[1];return r[1]},trys:[],ops:[]},l=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return l.next=a(0),l.throw=a(1),l.return=a(2),"function"==typeof Symbol&&(l[Symbol.iterator]=function(){return this}),l;function a(a){return function(c){return function(a){if(n)throw new TypeError("Generator is already executing.");for(;l&&(l=0,a[0]&&(i=0)),i;)try{if(n=1,o&&(r=2&a[0]?o.return:a[0]?o.throw||((r=o.return)&&r.call(o),0):o.next)&&!(r=r.call(o,a[1])).done)return r;switch(o=0,r&&(a=[2&a[0],r.value]),a[0]){case 0:case 1:r=a;break;case 4:return i.label++,{value:a[1],done:!1};case 5:i.label++,o=a[1],a=[0];continue;case 7:a=i.ops.pop(),i.trys.pop();continue;default:if(!((r=(r=i.trys).length>0&&r[r.length-1])||6!==a[0]&&2!==a[0])){i=0;continue}if(3===a[0]&&(!r||a[1]>r[0]&&a[1]<r[3])){i.label=a[1];break}if(6===a[0]&&i.label<r[1]){i.label=r[1],r=a;break}if(r&&i.label<r[2]){i.label=r[2],i.ops.push(a);break}r[2]&&i.ops.pop(),i.trys.pop();continue}a=t.call(e,i)}catch(e){a=[6,e],o=0}finally{n=r=0}if(5&a[0])throw a[1];return{value:a[0]?a[1]:void 0,done:!0}}([a,c])}}};function le(){return re(this,void 0,Promise,(function(){var e,t,n,o,r,i,l;return ie(this,(function(a){switch(a.label){case 0:return(e=(0,u.select)(de).getEditingProfileId())&&(t=(0,u.select)(q.store).getSocialProfile(e))?(0,u.select)(de).isSavingProfileSettings()?[2]:[4,(n=(0,u.dispatch)(de).markAsSavingProfileSettings)(!0)]:[2];case 1:a.sent(),a.label=2;case 2:return a.trys.push([2,5,,6]),o=(0,u.select)(de).getEditingProfileSettings(),r=(0,u.select)(q.store).getSiteId(),i=(0,u.select)(q.store).getApiRoot(),l=(0,u.select)(q.store).getAuthenticationToken(),[4,ne({url:"".concat(i,"/site/").concat(r,"/profile/").concat(e),method:"PUT",credentials:"omit",mode:"cors",headers:{Authorization:"Bearer ".concat(l)},data:o})];case 3:return a.sent(),[4,(0,u.dispatch)(q.store).receiveSocialProfiles(oe(oe({},t),o))];case 4:case 5:return a.sent(),[3,6];case 6:return[4,n(!1)];case 7:return a.sent(),[2]}}))}))}var ae=function(e,t,n,o){return new(n||(n=Promise))((function(r,i){function l(e){try{c(o.next(e))}catch(e){i(e)}}function a(e){try{c(o.throw(e))}catch(e){i(e)}}function c(e){var t;e.done?r(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(l,a)}c((o=o.apply(e,t||[])).next())}))},ce=function(e,t){var n,o,r,i={label:0,sent:function(){if(1&r[0])throw r[1];return r[1]},trys:[],ops:[]},l=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return l.next=a(0),l.throw=a(1),l.return=a(2),"function"==typeof Symbol&&(l[Symbol.iterator]=function(){return this}),l;function a(a){return function(c){return function(a){if(n)throw new TypeError("Generator is already executing.");for(;l&&(l=0,a[0]&&(i=0)),i;)try{if(n=1,o&&(r=2&a[0]?o.return:a[0]?o.throw||((r=o.return)&&r.call(o),0):o.next)&&!(r=r.call(o,a[1])).done)return r;switch(o=0,r&&(a=[2&a[0],r.value]),a[0]){case 0:case 1:r=a;break;case 4:return i.label++,{value:a[1],done:!1};case 5:i.label++,o=a[1],a=[0];continue;case 7:a=i.ops.pop(),i.trys.pop();continue;default:if(!((r=(r=i.trys).length>0&&r[r.length-1])||6!==a[0]&&2!==a[0])){i=0;continue}if(3===a[0]&&(!r||a[1]>r[0]&&a[1]<r[3])){i.label=a[1];break}if(6===a[0]&&i.label<r[1]){i.label=r[1],r=a;break}if(r&&i.label<r[2]){i.label=r[2],i.ops.push(a);break}r[2]&&i.ops.pop(),i.trys.pop();continue}a=t.call(e,i)}catch(e){a=[6,e],o=0}finally{n=r=0}if(5&a[0])throw a[1];return{value:a[0]?a[1]:void 0,done:!0}}([a,c])}}};function se(e){return ae(this,void 0,Promise,(function(){var t,n,o,r;return ce(this,(function(i){switch(i.label){case 0:return i.trys.push([0,6,,7]),[4,(0,u.dispatch)(de).markAsBeingDeleted(e)];case 1:return i.sent(),t=(0,u.select)(q.store).getSiteId(),n=(0,u.select)(q.store).getApiRoot(),o=(0,u.select)(q.store).getAuthenticationToken(),[4,ne({url:"".concat(n,"/site/").concat(t,"/profile/").concat(e),method:"DELETE",credentials:"omit",mode:"cors",headers:{Authorization:"Bearer ".concat(o)}})];case 2:return i.sent(),[4,(0,u.dispatch)(q.store).removeSocialProfile(e)];case 3:return i.sent(),r=(0,u.select)(q.store).getSocialProfiles(),[4,ne({path:"/nelio-content/v1/settings/update-profiles",method:"PUT",data:{profiles:!(0,I.isEmpty)(r)}})];case 4:return i.sent(),[4,(0,u.dispatch)(de).markAsDeleted(e)];case 5:case 6:return i.sent(),[3,7];case 7:return[2]}}))}))}function ue(){return ae(this,void 0,Promise,(function(){var e,t,n,o;return ce(this,(function(r){switch(r.label){case 0:return[4,(0,u.dispatch)(de).markAsRefreshtingProfiles(!0)];case 1:r.sent(),r.label=2;case 2:return r.trys.push([2,6,,7]),e=(0,u.select)(q.store).getSiteId(),t=(0,u.select)(q.store).getApiRoot(),n=(0,u.select)(q.store).getAuthenticationToken(),[4,ne({url:"".concat(t,"/site/").concat(e,"/profiles"),method:"GET",credentials:"omit",mode:"cors",headers:{Authorization:"Bearer ".concat(n)}})];case 3:return o=r.sent(),[4,ne({path:"/nelio-content/v1/settings/update-profiles",method:"PUT",data:{profiles:!(0,I.isEmpty)(o)}})];case 4:return r.sent(),[4,(0,u.dispatch)(q.store).receiveSocialProfiles(o)];case 5:case 6:return r.sent(),[3,7];case 7:return[4,(0,u.dispatch)(de).markAsRefreshtingProfiles(!1)];case 8:return r.sent(),[2]}}))}))}var fe=function(){return fe=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},fe.apply(this,arguments)},de=(0,u.createReduxStore)("nelio-content/profile-settings",{reducer:b,controls:u.controls,actions:fe(fe({},r),i),selectors:o});!function(e){(0,u.register)(e)}(de);var pe=[{title:(0,c._x)("Settings - Social Profiles","text","nelio-content"),intro:(0,c._x)("Welcome to the Social Profiles Settings Screen. Here you can manage the social accounts you connected to Nelio Content and customize its auto-publication limits.","user","nelio-content")},{title:(0,c._x)("Available Networks","text","nelio-content"),intro:(0,c._x)("To connect new profiles, click on its social network and follow the on-screen instructions.","user","nelio-content"),element:function(){return document.querySelector(".nelio-content-profile-connectors")}},{title:(0,c._x)("Social Profiles","text","nelio-content"),intro:(0,c._x)("Here you can find all your connected profiles. Hover on each profile to reveal additional actions like, for example, re-authenticating or deleting a profile.","user","nelio-content"),active:function(){return!!document.querySelector(".nelio-content-profile-list .nelio-content-profile")},element:function(){return document.querySelector(".nelio-content-profile-list")}},{title:(0,c._x)("Social Profiles","text","nelio-content"),intro:(0,c._x)("You can also add a fallback email address for each profile. Nelio Content will use it to let the recipient know when a social message couldn’t be automatically shared, thus offering them the opportunity to manually share it.","user","nelio-content"),active:function(){return!!document.querySelector(".nelio-content-profile-list .nelio-content-profile")},element:function(){return document.querySelector(".nelio-content-profile-list")}},{title:(0,c._x)("Social Automations","text","nelio-content"),intro:(0,c._x)("In the Automations tab you’ll find all the options you need to customize how Nelio Content should share your WordPress content on social media, including how many social messages should be generated to do so.","user","nelio-content"),active:function(){return!!document.querySelector("#nelio-content-automations")},element:function(){return document.querySelector("#nelio-content-automations")}}];const me=window.wp.components;var ge=function(){var e=he(),t=e.count,n=e.total;return t?l.createElement("div",{className:"nelio-content-profile-section-title"},l.createElement("h2",null,(0,c._x)("Connected Profiles","text","nelio-content")),!!n&&l.createElement("span",{className:"nelio-content-profile-section-title__profile-count"},t,"/",n)):l.createElement("div",{className:"nelio-content-profile-section-title"},l.createElement("h2",null,(0,c._x)("Add Profiles","text","nelio-content")))},he=function(){return f((function(e){var t=e(q.store),n=t.getSocialProfileCount,o=((0,t.getPluginLimits)()||{}).maxProfiles;return{count:n(),total:o===Number.POSITIVE_INFINITY?0:o}}))},ve=n(6942),Ee=n.n(ve),ye=function(e){var t=e.disabled,n=e.isUserAllowed,o=e.onClick;return l.createElement("div",{className:"nelio-content-profile__reauthenticate-action"},n?l.createElement(me.Button,{variant:"secondary",disabled:t,onClick:o},(0,c._x)("Re-Authenticate","command","nelio-content")):l.createElement(me.Tooltip,{placement:"bottom",text:(0,c._x)("Only the user who added this profile can re-authenticate it","user","nelio-content"),delay:0},l.createElement("span",{style:{opacity:.8}},(0,c._x)("Re-Authenticate","command","nelio-content"))))},be=function(e){var t=e.profileId,n=we(t),o=ke(t),r=Se(t),i=(0,q.useSocialProfile)(t),a=(0,u.useDispatch)(de),f=a.deleteProfile,d=a.refreshProfile,p=(0,u.useDispatch)(de).openProfileEditor,m=xe(t),g=m.isPublicationEnabled,h=m.isReshareEnabled;if(!i)return null;var v=i.displayName,E=i.status;return l.createElement("div",{className:Ee()({"nelio-content-profile":!0,"nelio-content-profile--is-locked":r,"nelio-content-profile--is-invalid":"valid"!==E})},l.createElement("div",{className:"nelio-content-profile__name"},v,g&&l.createElement(me.Tooltip,{placement:"bottom",text:(0,c._x)("This profile can automatically share new content when it’s published","text","nelio-content"),delay:0},l.createElement(me.Dashicon,{className:"nelio-content-profile__automation-icon",icon:"megaphone"})),h&&l.createElement(me.Tooltip,{text:(0,c._x)("This profile might reshare old content daily","text","nelio-content"),placement:"bottom",delay:0},l.createElement(me.Dashicon,{className:"nelio-content-profile__automation-icon",icon:"share-alt"}))),l.createElement("div",{className:"nelio-content-profile__icon"},l.createElement(s.SocialProfileIcon,{profileId:t})),l.createElement("div",{className:"nelio-content-profile__details"},!!i.isBuffer&&l.createElement(l.Fragment,null,l.createElement("span",null,(0,c._x)("Buffer Profile","text","nelio-content")),l.createElement("span",null,"|")),!!i.isHootsuite&&l.createElement(l.Fragment,null,l.createElement("span",null,(0,c._x)("Hootsuite Profile","text","nelio-content")),l.createElement("span",null,"|")),l.createElement(s.ProfileCreator,{profileId:t}),!!i.email&&l.createElement(l.Fragment,null,l.createElement("span",null,"|"),l.createElement("span",null,Ne(i.email)))),"renew"===E&&l.createElement(ye,{isUserAllowed:n,disabled:r,onClick:function(){d(t)}}),r?l.createElement("div",{className:"nelio-content-profile__feedback"},o?l.createElement(s.DeleteButton,{isDeleting:o,onClick:function(){}}):l.createElement("br",null)):l.createElement("div",{className:"nelio-content-profile__actions"},"renew"!==E&&l.createElement(l.Fragment,null,l.createElement(_e,{profileId:t})," | ",l.createElement(me.Button,{variant:"link",onClick:function(){var e,t;p(i.id,{email:null!==(e=i.email)&&void 0!==e?e:"",permalinkQueryArgs:null!==(t=i.permalinkQueryArgs)&&void 0!==t?t:[]})}},(0,c._x)("Settings","text","nelio-content"))," | "),l.createElement(s.DeleteButton,{onClick:function(){f(t)},confirmationLabels:{title:(0,c._x)("Delete Social Profile and Templates","text","nelio-content"),text:(0,c._x)("Are you sure you want to delete this profile? If you do so, you’ll also delete any social templates you defined for this profile in the Automations tab. This operation cannot be undone.","user","nelio-content")}})))},_e=function(e){var t=e.profileId,n=we(t),o=(0,u.useDispatch)(de).refreshProfile;return n?l.createElement(me.Button,{variant:"link",onClick:function(){o(t)}},(0,c._x)("Refresh","command","nelio-content")):l.createElement(me.Tooltip,{placement:"bottom",text:(0,c._x)("Only the user who added this profile can refresh it","user","nelio-content"),delay:0},l.createElement("span",null,(0,c._x)("Refresh","command","nelio-content")))},we=function(e){return f((function(t){var n=t(q.store).getCurrentUserId(),o=t(q.store).getSocialProfile(e);return(null==o?void 0:o.creatorId)===n}))},ke=function(e){return f((function(t){return t(de).isProfileBeingDeleted(e)}))},Se=function(e){return f((function(t){return t(de).isProfileBeingDeleted(e)}))},xe=function(e){return f((function(t){var n=(0,v.map)(t(q.store).getAutomationGroups(),t(q.store).getAutomationGroup).filter(I.isDefined).filter((function(e){return!!e.priority})).filter((function(t){var n;return!!(null===(n=t.profileSettings[e])||void 0===n?void 0:n.enabled)}));return{isPublicationEnabled:n.some((function(t){var n;return!!(null===(n=t.profileSettings[e])||void 0===n?void 0:n.publication.enabled)})),isReshareEnabled:n.some((function(t){var n;return!!(null===(n=t.profileSettings[e])||void 0===n?void 0:n.reshare.enabled)}))}}))},Ne=function(e){var t=e.split("@"),n=t[0],o=void 0===n?"":n,r=t[1],i=void 0===r?"":r,l={length:10,omission:"…"};return"".concat((0,v.truncate)(o,l),"@").concat((0,v.truncate)(i,l))},Pe=function(){var e=(0,v.reverse)((0,v.sortBy)((0,q.useSocialProfiles)(),"creationDate"));return(0,I.isEmpty)(e)?l.createElement("p",null,(0,c._x)("Connect your social media profiles to Nelio Content using the following buttons:","user","nelio-content")):l.createElement(l.Fragment,null,l.createElement("p",null,(0,c._x)("The following profiles can be managed by any author in your team:","user","nelio-content")),l.createElement("div",{className:"nelio-content-profile-list"},e.map((function(e){var t=e.id;return l.createElement(be,{key:"nelio-content-profile-".concat(t),profileId:t})}))))},Ie=function(e){var t=e.network,n=e.disabled,o=(0,u.useDispatch)(de),r=o.openConnectionDialog,i=o.openKindSelectorDialog,a=(0,q.useIsSubscribed)(),f=Oe(t),d=(0,H.getNetworkLabel)("add",t),p=(0,H.getNetworkKinds)(t).length?(0,H.getNetworkKinds)(t):[{id:"single",label:(0,H.getNetworkLabel)("name",t)}],m=Ce(t)((function(){1!==p.length||(0,H.doesNetworkSupport)("buffer-connection",t)||(0,H.doesNetworkSupport)("hootsuite-connection",t)?i(t):r(t,p[0].id)}));return a&&!f?l.createElement(me.Tooltip,{placement:"bottom",text:(0,c._x)("You’ve reached the maximum number of allowed profiles","user","nelio-content")},l.createElement("div",{className:"nelio-content-profile-connectors__connect-button"},l.createElement(s.SocialNetworkIcon,{network:t,disabled:!0}))):n?l.createElement("div",{className:"nelio-content-profile-connectors__connect-button"},l.createElement(s.SocialNetworkIcon,{network:t,disabled:!0})):l.createElement(me.Tooltip,{position:"bottom center",text:d},l.createElement(me.Button,{className:Ee()({"nelio-content-profile-connectors__connect-button":!0,"nelio-content-profile-connectors__connect-button--is-blurred":!f}),variant:"link",onClick:m},l.createElement(s.SocialNetworkIcon,{network:t})))},Oe=function(e){return"available"===Ae(e)},Ce=function(e){var t=Ae(e);return(0,q.useFeatureGuard)("network-locked"===t?"settings/more-profiles-in-network":"settings/more-profiles","available"!==t)},Ae=function(e){return f((function(t){var n=t(q.store),o=n.getPluginLimits,r=n.getSocialProfiles,i=o(),l=i.maxProfiles,a=i.maxProfilesPerNetwork,c=r();return c.length>=l?"all-networks-locked":(0,v.filter)(c,{network:e}).length>=a?"network-locked":"available"}))},De=function(e){var t=e.disabled;return l.createElement("div",{className:"nelio-content-profile-connectors"},(0,H.getSupportedNetworks)().map((function(e){return l.createElement(Ie,{key:e,network:e,disabled:t})})))},Re=[{type:"buffer",support:"buffer-connection",label:"Buffer"},{type:"hootsuite",support:"hootsuite-connection",label:"Hootsuite"}],Te=function(e){var t=e.network,n=(0,H.getNetworkKinds)(t).length?(0,H.getNetworkKinds)(t):[{id:"single",label:(0,H.getNetworkLabel)("name",t)}],o=f((function(e){return e(q.store).isSubscribed()})),r=(0,u.useDispatch)(de),i=r.closeKindSelectorDialog,a=r.openConnectionDialog,d=i,p=Re.filter((function(e){var n=e.support;return(0,H.doesNetworkSupport)(n,t)}));return l.createElement(me.Modal,{className:"nelio-content-kind-selector-dialog",title:(0,c._x)("Connect Profile","text","nelio-content"),isDismissible:!0,shouldCloseOnEsc:!0,shouldCloseOnClickOutside:!0,onRequestClose:d},l.createElement("div",{className:"nelio-content-kind-selector-dialog__list"},n.map((function(e){var n=e.id,r=e.label;return l.createElement(me.Button,{key:"nc-".concat(t,"-kind-").concat(n),className:"nelio-content-kind-selector-dialog__connect-button",variant:"link",onClick:function(){return e=n,i(),void a(t,e);var e},disabled:"twitter"===t&&!o},l.createElement(s.SocialNetworkIcon,{className:"nelio-content-kind-selector-dialog__profile-icon",network:t,kind:n}),l.createElement("span",null,r))}))),!!p.length&&l.createElement("p",{className:"nelio-content-kind-selector-dialog__alternative"},(0,l.createInterpolateElement)((0,c.sprintf)(/* translators: an alternative connection method, like “Buffer” */ /* translators: an alternative connection method, like “Buffer” */
(0,c._x)("Or connect via %s","user","nelio-content"),(0,I.listify)("or",p.map((function(e){var t=e.type,n=e.label;return"<".concat(t,">").concat(n,"</").concat(t,">")})))),(0,v.mapValues)((0,v.keyBy)(p,"type"),(function(e){var n=e.type;return l.createElement(me.Button,{variant:"link",onClick:function(){return e=n,i(),void a(e,t);var e}})})))),"twitter"===t&&l.createElement(Le,null))},Le=function(){var e=f((function(e){return e(q.store).isSubscribed()}));return l.createElement("p",{className:"nelio-content-kind-selector-dialog__notice"},l.createElement("strong",null,(0,c._x)("Notice:","text","nelio-content"))," ",e?(0,c._x)("Due to recent changes in X’s API, messages posted through a native connection may be restricted, as Nelio Content has a maximum limit of requests allowed for a specific time frame.","text","nelio-content"):(0,c._x)("Due to recent changes in X’s API, native connections of X profiles are only available to Nelio Content subscribers. If you want to connect a X profile, please use Buffer or Hootsuite instead.","text","nelio-content"))},Be=function(){var e=f((function(e){return!!e(de).getConnectionDialog()})),t=f((function(e){return e(de).isProfileListRefreshing()})),n=f((function(e){return e(de).getKindDialog()})),o=!!n;return(0,l.useEffect)((function(){document.body.style.overflow=t||e?"hidden":""}),[t,e]),l.createElement("div",{className:"nelio-content-connection-dialog"},e&&l.createElement("div",{className:"nelio-content-connection-dialog__overlay"},l.createElement("div",{className:"nelio-content-connection-dialog__overlay-content"},(0,c._x)("Please follow the instructions to authenticate the social profile…","user","nelio-content"))),o&&l.createElement(Te,{network:n}),t&&l.createElement("div",{className:"nelio-content-connection-dialog__overlay"},l.createElement("div",{className:"nelio-content-connection-dialog__overlay-content"},(0,c._x)("Refreshing social profiles…","text","nelio-content"))))},Ge=function(){var e=Fe(),t=e[0],n=e[1];return l.createElement(l.Fragment,null,l.createElement("div",{className:"nelio-content-profile-settings-dialog__title"},(0,c._x)("Fallback Email","text","nelio-content")),l.createElement(me.TextControl,{value:t,onChange:n,placeholder:(0,c._x)("Email","text","nelio-content"),help:(0,c._x)("Nelio Content will use this email to let the recipient know when a social message couldn’t be shared, thus offering them the opportunity to manually share it.","text","nelio-content")}))},Fe=function(){return[f((function(e){return(0,v.trim)(e(de).getEditingEmail()).replace(/^mailto:/i,"")})),(0,u.useDispatch)(de).setEditingEmail]},je=function(e,t,n){if(n||2===arguments.length)for(var o,r=0,i=t.length;r<i;r++)!o&&r in t||(o||(o=Array.prototype.slice.call(t,0,r)),o[r]=t[r]);return e.concat(o||Array.prototype.slice.call(t))},Ke=function(){var e=Qe(),t=e.items,n=e.add,o=e.replace;return l.createElement("div",{className:"nelio-content-profile-settings-args"},l.createElement("div",{className:"nelio-content-profile-settings-args__name"},l.createElement("span",null,(0,c._x)("URL Parameters","text","nelio-content")),l.createElement(me.Button,{variant:"link",onClick:n},(0,c._x)("Add New","command (URL parameter)","nelio-content"))),l.createElement("div",{className:"nelio-content-profile-settings-args__list"},t.map((function(e,t){return l.createElement(Me,{key:t,name:e[0],value:e[1],isOverwriteable:e[2],showLabel:!t,onNameChange:function(t){return o(e,e[2]?[t,e[1],!0]:[t,e[1]])},onValueChange:function(t){return o(e,e[2]?[e[0],t,!0]:[e[0],t])},onOverwriteChange:function(t){return o(e,t?[e[0],e[1],!0]:[e[0],e[1]])}})}))),l.createElement("div",{className:"nelio-content-profile-settings-args__help"},(0,c.sprintf)(/* translators: a placeholder name */ /* translators: a placeholder name */
(0,c._x)("Add URL parameters to links inserted with the %s placeholder. To remove parameters, leave their names empty.","user","nelio-content"),"{permalink}")))},Me=function(e){var t=e.name,n=e.value,o=e.showLabel,r=e.isOverwriteable,i=e.onNameChange,a=e.onValueChange,s=e.onOverwriteChange;return l.createElement("div",{className:"nelio-content-profile-settings-args__item"},l.createElement(me.TextControl,{label:o?(0,c._x)("Name","text","nelio-content"):void 0,placeholder:(0,c._x)("Name","text","nelio-content"),value:t,onChange:i}),l.createElement(me.TextControl,{label:o?(0,c._x)("Value (optional)","text","nelio-content"):void 0,placeholder:(0,c._x)("Value (optional)","text","nelio-content"),value:n,onChange:a}),o?l.createElement(me.BaseControl,{id:"nelio-content-profile-settings-args__first-query-arg",label:(0,c._x)("Fallback","text (query arg)","nelio-content")},l.createElement(me.ToggleControl,{label:"",checked:r,onChange:s})):l.createElement(me.ToggleControl,{label:"",checked:r,onChange:s}))},Qe=function(){var e=f((function(e){return e(de).getEditingQueryArgs()})),t=(0,u.useDispatch)(de).setEditingQueryArgs;return{items:e,add:function(){return t(je(je([],e,!0),[["",""]],!1))},replace:function(n,o){return t(e.map((function(e){return e===n?o:e})))}}},qe=function(){var e=He(),t=Ue(),n=(0,u.useDispatch)(de),o=n.saveProfileSettings,r=n.closeProfileEditor;return"closed"===t?null:l.createElement(me.Modal,{className:"nelio-content-profile-settings-dialog",title:(0,c._x)("Profile Settings","text","nelio-content"),isDismissible:!1,shouldCloseOnEsc:!1,shouldCloseOnClickOutside:!1,onRequestClose:function(){}},l.createElement(Ge,null),l.createElement(Ke,null),l.createElement("div",{className:"nelio-content-profile-settings-dialog__actions"},l.createElement(me.Button,{variant:"secondary",disabled:"saving"===t,onClick:r},"open-dirty"===t?(0,c._x)("Discard Changes","command","nelio-content"):(0,c._x)("Cancel","command","nelio-content")),l.createElement(s.SaveButton,{error:e||void 0,variant:"primary",disabled:"open-clean"===t,isSaving:"saving"===t,onClick:function(){o().then(r)}})))},He=function(){return f((function(e){var t=(0,v.trim)(e(de).getEditingEmail()).replace(/^mailto:/i,"");if(t&&!(0,Q.isEmail)(t))return(0,c._x)("Please write a valid email address","user","nelio-content")}))},Ue=function(){return f((function(e){var t,n;e(q.store);var o=e(de).getEditingProfileId();if(!o)return"closed";if(e(de).isSavingProfileSettings())return"saving";var r=e(de).getEditingProfileSettings(),i=e(q.store).getSocialProfile(o),l={email:null!==(t=null==i?void 0:i.email)&&void 0!==t?t:"",permalinkQueryArgs:null!==(n=null==i?void 0:i.permalinkQueryArgs)&&void 0!==n?n:[]};return(0,v.isEqual)(l,r)?"open-clean":"open-dirty"}))},Ve=function(){var e=f((function(e){return!e(q.store).hasFinishedResolution("getSocialProfiles")})),t=f((function(e){return e(de).areThereProfilesBeingDeleted()}));return e?l.createElement("div",{className:"nelio-content-social-profiles-layout"},l.createElement(s.LoadingAnimation,null)):l.createElement(l.StrictMode,null,l.createElement(me.SlotFillProvider,null,l.createElement("div",{className:"nelio-content-social-profiles-layout"},l.createElement("div",{className:"nelio-content-social-profiles-layout__title-wrapper"},l.createElement(ge,null)),l.createElement(Pe,null),l.createElement(De,{disabled:t}),l.createElement(Be,null),l.createElement(qe,null),l.createElement(s.PremiumDialog,null),l.createElement(me.Popover.Slot,null))))};function ze(e){var t=document.getElementById(e);a(l.createElement(Ve,null),t);var n=document.getElementById("nelio-content-settings-title");n&&a(l.createElement(s.ContextualHelp,{context:"social-profiles",walkthrough:pe,autostart:!0,component:We}),n)}var We=function(e){var t=e.className,n=e.runWalkthrough;return l.createElement("button",{className:"".concat(t,"  page-title-action"),onClick:n},(0,c._x)("Help","text","nelio-content"))}},6942:(e,t)=>{var n;!function(){"use strict";var o={}.hasOwnProperty;function r(){for(var e="",t=0;t<arguments.length;t++){var n=arguments[t];n&&(e=l(e,i(n)))}return e}function i(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return r.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var n in e)o.call(e,n)&&e[n]&&(t=l(t,n));return t}function l(e,t){return t?e?e+" "+t:e+t:e}e.exports?(r.default=r,e.exports=r):void 0===(n=function(){return r}.apply(t,[]))||(e.exports=n)}()}},n={};function o(e){var r=n[e];if(void 0!==r)return r.exports;var i=n[e]={exports:{}};return t[e](i,i.exports,o),i.exports}o.m=t,e=[],o.O=(t,n,r,i)=>{if(!n){var l=1/0;for(u=0;u<e.length;u++){n=e[u][0],r=e[u][1],i=e[u][2];for(var a=!0,c=0;c<n.length;c++)(!1&i||l>=i)&&Object.keys(o.O).every((e=>o.O[e](n[c])))?n.splice(c--,1):(a=!1,i<l&&(l=i));if(a){e.splice(u--,1);var s=r();void 0!==s&&(t=s)}}return t}i=i||0;for(var u=e.length;u>0&&e[u-1][2]>i;u--)e[u]=e[u-1];e[u]=[n,r,i]},o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var n in t)o.o(t,n)&&!o.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),o.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e={9268:0,3183:0};o.O.j=t=>0===e[t];var t=(t,n)=>{var r,i,l=n[0],a=n[1],c=n[2],s=0;if(l.some((t=>0!==e[t]))){for(r in a)o.o(a,r)&&(o.m[r]=a[r]);if(c)var u=c(o)}for(t&&t(n);s<l.length;s++)i=l[s],o.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return o.O(u)},n=self.webpackChunkNelioContent=self.webpackChunkNelioContent||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var r=o.O(void 0,[3183],(()=>o(9167)));r=o.O(r);var i=NelioContent="undefined"==typeof NelioContent?{}:NelioContent;for(var l in r)i[l]=r[l];r.__esModule&&Object.defineProperty(i,"__esModule",{value:!0})})();