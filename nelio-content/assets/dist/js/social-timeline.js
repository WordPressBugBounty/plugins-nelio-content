(()=>{var e,t={4553:(e,t,n)=>{"use strict";n.r(t),n.d(t,{socialTimeline:()=>o});var o={};n.r(o),n.d(o,{SocialTimeline:()=>G});const r=window.wp.element,i=window.wp.i18n,a=window.wp.components,s=window.wp.data;function l(e,t){return(0,s.useSelect)(e,t)}var c=n(6942),u=n.n(c);const m=window.moment;var d=n.n(m);const p=window.lodash,f=window.NelioContent.data,g=window.NelioContent.utils,v=window.NelioContent.socialMessageEditor;var y=function(e,t){switch(e){case"day":return t<1;case"week":return 1<=t&&t<=10;case"month":return 10<t&&t<=40;default:return 40<t}};const h=window.NelioContent.components;var _=function(e){var t,n=e.message,o=e.deleteMessage,s=e.isBeingDeleted,c=e.isTimelineBusy,m=e.post,d=l((function(e){return e(f.store).canCurrentUserEditSocialMessage(n)})),p=l((function(e){return e(f.store).canCurrentUserDeleteSocialMessage(n)})),v=b(n,m),y=!["error","publish"].includes(null!==(t=null==n?void 0:n.status)&&void 0!==t?t:""),_=(0,i._x)("View","command","nelio-content"),w=(0,g.isRecurringMessage)(n)?(0,i._x)("Edit All","command (all recurring messages)","nelio-content"):(0,i._x)("Edit","command","nelio-content");return r.createElement("div",{className:u()({"nelio-content-social-message__actions":!0,"nelio-content-social-message__actions--is-deleting":s})},!s&&r.createElement(a.Button,{variant:"link",disabled:!d||c,onClick:v},y?w:_),!s&&!!o&&r.createElement("span",null,"|"),!!o&&r.createElement(h.DeleteButton,{isDeleting:s,onClick:function(){(null==n?void 0:n.id)&&o(n.id)},disabled:!p||c,labels:{delete:(0,g.isRecurringMessage)(n)?(0,i._x)("Delete All","command (all recurring messages)","nelio-content"):(0,i._x)("Delete","command","nelio-content"),deleting:(0,i._x)("Deleting…","text","nelio-content")},confirmationLabels:{title:(0,g.isRecurringMessage)(n)?(0,i._x)("Delete Recurring Social Message","text","nelio-content"):(0,i._x)("Delete Social Message","text","nelio-content"),text:(0,g.isRecurringMessage)(n)?(0,i._x)("Are you sure you want to delete this recurring social message and all its following instances? This operation can’t be undone.","user","nelio-content"):(0,i._x)("Are you sure you want to delete this social message? This operation can’t be undone.","user","nelio-content")}}))},b=function(e,t){var n=(0,s.useDispatch)(v.store).openSocialMessageEditor;return function(){e&&n(e,{context:"post",post:t})}};const w=window.NelioContent.networks;var x=function(e){var t=e.network,n=e.post,o=e.text,i=(0,g.computeSocialMessageText)((0,w.getCharLimitInNetwork)(t),n,o);return r.createElement("div",{className:"nelio-content-social-message__content-wrapper"},r.createElement(h.SocialMessageTextPreview,{className:"nelio-content-social-message__actual-content",placeholder:"",linkBeautifier:function(e){if(-1===(e=e.replace(/^https?:\/\//i,"")).indexOf("/"))return e;var t=e.replace(/^[^/]*\//,"");if(t.length<=10)return e;var n=e.replace(/\/.*$/,"");return"".concat(n,"/").concat(t.substring(0,9),"…")},value:i}))};const E=window.NelioContent.date;var N=function(e){var t=e.isBeingDeleted,n=e.source,o=function(e){var t=e.dateType,n=e.schedule;if("publish"===e.postStatus||"exact"===t)return(0,E.dateI18n)((0,E.getSettings)().formats.datetime,n);var o=e.dateValue,r=e.timeType,a=e.timeValue;if("0"===o){if("exact"===r)return a;if("0"===a)return(0,i._x)("Same time as publication","text","nelio-content");var s=Math.abs(Number.parseInt(a))||0;return(0,i.sprintf)(/* translators: number of hours */ /* translators: number of hours */
(0,i._nx)("%d hour after publication","%d hours after publication",s,"text","nelio-content"),s)}var l=Math.abs(Number.parseInt(o))||0;return(0,i.sprintf)(/* translators: 1 -> number of days, 2 -> time */ /* translators: 1 -> number of days, 2 -> time */
(0,i._nx)("%1$d day after publication at %2$s","%1$d days after publication at %2$s",l,"text","nelio-content"),l,a)}(e);return r.createElement("div",{className:u()({"nelio-content-social-message__date":!0,"nelio-content-social-message__date--is-deleting":t})},"reshare-template"===n&&r.createElement(a.Dashicon,{icon:"share-alt"}),"publication-template"===n&&r.createElement(a.Dashicon,{icon:"megaphone"}),["user-highlight","custom-sentence"].includes(n)&&r.createElement(a.Dashicon,{icon:"editor-quote"}),"auto-extracted-sentence"===n&&r.createElement(a.Dashicon,{icon:"format-status"}),o)},S=function(e){var t=e.messageId,n=O(t);return n?r.createElement("div",{className:"nelio-content-social-message__recurrence"},r.createElement(a.Dashicon,{icon:"controls-repeat"}),n):null},M={period:"day",occurrences:2,interval:1},O=function(e){var t,n=l((function(t){return{message:t(f.store).getSocialMessage(e),today:t(f.store).getToday()}})),o=n.message,r=n.today,i=(0,f.useRecurrenceSummary)((0,g.createRecurrenceContext)((null==o?void 0:o.schedule)||r),null!==(t=null==o?void 0:o.recurrenceSettings)&&void 0!==t?t:M);return(null==o?void 0:o.recurrenceSettings)?i:void 0},T=function(e){var t=k(e);return r.createElement("div",{className:"nelio-content-social-message__location"},t)},k=function(e){return l((function(t){var n,o,a=e.network,s=e.profileId,l=e.targetName,c=(0,t(f.store).getSocialProfile)(s),u=(null!==(n=null==c?void 0:c.alias)&&void 0!==n?n:null==c?void 0:c.displayName)||(0,i._x)("Unknown","text","nelio-content");if(!l||!(0,w.doesNetworkSupport)("multi-target",a))return r.createElement("strong",null,u);var m=(0,t(f.store).getProfileTarget)(s,l),d=null!==(o=null==m?void 0:m.displayName)&&void 0!==o?o:"";return(0,g.isEmpty)(d)?r.createElement("strong",null,u):(0,r.createInterpolateElement)((0,i.sprintf)(/* translators: 1 -> profile name, 2 -> target name */ /* translators: 1 -> profile name, 2 -> target name */
(0,i._x)("%1$s on %2$s","text","nelio-content"),"<strong>".concat(u,"</strong>"),"<em>".concat(d,"</em>")),{strong:r.createElement("strong",null),em:r.createElement("em",null)})}))},D=function(e){var t=e.messageId,n=e.deleteMessage,o=e.isBeingDeleted,c=e.isTimelineBusy,m=e.post,d=(0,s.useDispatch)(f.store).openPremiumDialog,g=l((function(e){return e(f.store).getSocialMessage(t)}));if(!g)return null;var v=g.auto,y=g.dateType,b=g.dateValue,w=g.id,E=g.isFreePreview,M=g.image,O=g.network,k=g.profileId,D=g.schedule,C=g.source,I=g.status,j=g.targetName,B=g.text,V=g.timeType,R=g.timeValue,A=g.type,$="error"===I,U="image"===A||"auto-image"===A,F="video"===A,G="multi-media"===A;return r.createElement("div",{className:u()({"nelio-content-social-message":!0,"nelio-content-social-message--is-auto":!!v,"nelio-content-social-message--is-error":$,"nelio-content-social-message--is-free-preview":E}),"data-social-message-id":w},U&&r.createElement(a.Dashicon,{className:"nelio-content-social-message__image-type-icon",icon:(0,p.isArray)(M)&&M.length>1?"format-gallery":"format-image"}),F&&r.createElement(a.Dashicon,{className:"nelio-content-social-message__video-type-icon",icon:"video-alt3"}),G&&r.createElement(a.Dashicon,{className:"nelio-content-social-message__multi-media-type-icon",icon:"format-gallery"}),r.createElement("div",{className:"nelio-content-social-message__profile-and-location"},r.createElement(h.SocialProfileIcon,{className:"nelio-content-social-message__profile-icon",profileId:k}),r.createElement(T,{network:O,profileId:k,targetName:j})),r.createElement(x,{network:O,post:m,text:B||P(A)}),r.createElement(S,{messageId:t}),r.createElement(N,{isBeingDeleted:o,dateType:y,dateValue:b,schedule:D,timeType:V,timeValue:R,source:C,postStatus:m.status}),r.createElement(_,{message:g,deleteMessage:n,isTimelineBusy:c,isBeingDeleted:o,post:m}),E&&r.createElement("button",{className:"nelio-content-social-message__free-preview-button",onClick:function(){return d("raw/free-previews")}},r.createElement("div",{className:"nelio-content-social-message__premium-badge components-button nelio-content-social-media-timeline-period__add-button nelio-content-premium-feature-button nelio-content-premium-feature-button--is-premium is-small has-text has-icon"},r.createElement("span",null,r.createElement(a.Dashicon,{icon:"lock"})),r.createElement("span",null,(0,i._x)("Premium","text","nelio-content")))))};function P(e){switch(e){case"auto-image":case"text":return"";case"image":return(0,i._x)("(image)","text","nelio-content");case"video":return(0,i._x)("(video)","text","nelio-content");case"multi-media":return(0,i._x)("(media)","text","nelio-content")}}var C=function(){return C=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},C.apply(this,arguments)},I=function(e){var t=e.period,n=e.deletingMessageIds,o=void 0===n?[]:n,s=function(e,t){var n={};for(var o in e)Object.prototype.hasOwnProperty.call(e,o)&&t.indexOf(o)<0&&(n[o]=e[o]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var r=0;for(o=Object.getOwnPropertySymbols(e);r<o.length;r++)t.indexOf(o[r])<0&&Object.prototype.propertyIsEnumerable.call(e,o[r])&&(n[o[r]]=e[o[r]])}return n}(e,["period","deletingMessageIds"]),l=s.post,c=s.isTimelineBusy,m=void 0!==c&&c,d=j(t,l),p=(0,f.useIsSubscribed)(),v=B(t,l,m),y=V(l,m),_=R(l,m),b=_&&!y;return r.createElement("div",{className:"nelio-content-social-media-timeline-period__period-content"},!(0,g.isEmpty)(d)&&r.createElement("div",{className:"nelio-content-social-media-timeline-period__messages"},d.map((function(e){return r.createElement(D,C({key:e,messageId:e,isBeingDeleted:o.includes(e)},s))}))),r.createElement("div",{className:"nelio-content-social-media-timeline-period__add-button-wrapper"},p||"day"===t?r.createElement(a.Button,{className:u()({"nelio-content-social-media-timeline-period__add-button":!0,"nelio-content-premium-feature-button nelio-content-premium-feature-button--is-premium":b}),variant:b?void 0:"secondary",size:"small",disabled:!_,onClick:v,icon:b?"lock":void 0},(0,i._x)("Add Social Message","command","nelio-content")):r.createElement(h.PremiumFeatureButton,{feature:"edit-post/custom-timeline",label:(0,i._x)("Add Social Message","command","nelio-content"),size:"small"})))},j=function(e,t){return l((function(n){var o=(0,n(f.store).getSocialMessagesRelatedToPost)(t.id).filter((function(e){return"publish"!==e.status})).filter((function(e,t,n){return!(0,g.isRecurringMessage)(e)||A(n,e)})),r=n(f.store).getUtcNow,i="publish"===t.status?function(e,t){return function(n){var o=n.schedule,r=Math.abs(t.diff(o,"days"));return y(e,r)}}(e,d()(r())):function(e){return function(t){var n=t.dateType,o=t.dateValue;if("exact"===n)return"other"===e;var r=Math.abs(Number.parseInt(o)||0);return y(e,r)}}(e);return(0,p.map)((0,p.filter)(o,i),"id")}))},B=function(e,t,n){var o=V(t,n),r=R(t,n),i=(0,f.useFeatureGuard)("edit-post/create-more-messages",r&&!o),a=function(e){switch(e){case"day":return{dateType:"predefined-offset",dateValue:"0"};case"week":return{dateType:"predefined-offset",dateValue:"7",timeType:"time-interval",timeValue:"morning"};case"month":return{dateType:"predefined-offset",dateValue:"28",timeType:"time-interval",timeValue:"morning"};default:return{dateType:"exact",timeType:"time-interval",timeValue:"morning"}}}(e),c=l((function(e){return e(f.store).getProfilesWithMessagesRelatedToPost(t.id)})),u=(0,s.useDispatch)(v.store).openNewSocialMessageEditor;return i((function(){return u(a,{context:"post",post:t,disabledProfileIds:c})}))},V=function(e,t){return l((function(n){return n(f.store).canCurrentUserCreateMessagesRelatedToPost(e)&&!t}))},R=function(e,t){return l((function(n){return n(f.store).couldSubscriberCreateMessagesRelatedToPost(e)&&!t}))},A=function(e,t){var n=e.filter((function(e){return e.recurrenceGroup===t.recurrenceGroup}));return(0,p.sortBy)(n,"schedule")[0]===t},$=function(){return $=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},$.apply(this,arguments)},U=function(e){var t=function(e,t){switch(e){case"day":return"publish"===t.status?(0,i._x)("Today","text","nelio-content"):(0,i._x)("Publication Day","text","nelio-content");case"week":return(0,i._x)("Week","text","nelio-content");case"month":return(0,i._x)("Month","text","nelio-content");case"other":return(0,i._x)("Other","text","nelio-content")}}(e.period,e.post);return r.createElement("div",{className:"nelio-content-social-media-timeline-period"},r.createElement("div",{className:"nelio-content-social-media-timeline-period__title"},t),r.createElement(I,$({},e)))},F=function(){return F=Object.assign||function(e){for(var t,n=1,o=arguments.length;n<o;n++)for(var r in t=arguments[n])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},F.apply(this,arguments)},G=function(e){return r.createElement("div",{className:"nelio-content-social-timeline"},r.createElement(U,F({period:"day"},e)),r.createElement(U,F({period:"week"},e)),r.createElement(U,F({period:"month"},e)),r.createElement(U,F({period:"other"},e)))}},6942:(e,t)=>{var n;!function(){"use strict";var o={}.hasOwnProperty;function r(){for(var e="",t=0;t<arguments.length;t++){var n=arguments[t];n&&(e=a(e,i(n)))}return e}function i(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return r.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var n in e)o.call(e,n)&&e[n]&&(t=a(t,n));return t}function a(e,t){return t?e?e+" "+t:e+t:e}e.exports?(r.default=r,e.exports=r):void 0===(n=function(){return r}.apply(t,[]))||(e.exports=n)}()}},n={};function o(e){var r=n[e];if(void 0!==r)return r.exports;var i=n[e]={exports:{}};return t[e](i,i.exports,o),i.exports}o.m=t,e=[],o.O=(t,n,r,i)=>{if(!n){var a=1/0;for(u=0;u<e.length;u++){n=e[u][0],r=e[u][1],i=e[u][2];for(var s=!0,l=0;l<n.length;l++)(!1&i||a>=i)&&Object.keys(o.O).every((e=>o.O[e](n[l])))?n.splice(l--,1):(s=!1,i<a&&(a=i));if(s){e.splice(u--,1);var c=r();void 0!==c&&(t=c)}}return t}i=i||0;for(var u=e.length;u>0&&e[u-1][2]>i;u--)e[u]=e[u-1];e[u]=[n,r,i]},o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var n in t)o.o(t,n)&&!o.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),o.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e={1482:0,5001:0};o.O.j=t=>0===e[t];var t=(t,n)=>{var r,i,a=n[0],s=n[1],l=n[2],c=0;if(a.some((t=>0!==e[t]))){for(r in s)o.o(s,r)&&(o.m[r]=s[r]);if(l)var u=l(o)}for(t&&t(n);c<a.length;c++)i=a[c],o.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return o.O(u)},n=self.webpackChunkNelioContent=self.webpackChunkNelioContent||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var r=o.O(void 0,[5001],(()=>o(4553)));r=o.O(r);var i=NelioContent="undefined"==typeof NelioContent?{}:NelioContent;for(var a in r)i[a]=r[a];r.__esModule&&Object.defineProperty(i,"__esModule",{value:!0})})();