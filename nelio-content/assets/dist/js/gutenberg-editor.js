(()=>{var e={4396:(e,t)=>{"use strict";function n(e){return e}t.L8=void 0,t.L8=function(){return n}}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var i=t[r]={exports:{}};return e[r](i,i.exports,n),i.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})};var r={};(()=>{"use strict";n.r(r),n.d(r,{initPage:()=>me});const e=window.wp.data;function t(t,n){return(0,e.useSelect)(t,n)}const o=window.wp.editor,i=window.NelioContent.editPost,a=window.NelioContent.utils,l=window.wp.element,s=window.wp.apiFetch,u=n.n(s)(),c=window.lodash,d=window.NelioContent.data,f=window.NelioContent.socialMessageEditor,g=window.wp.domReady,m=n.n(g)();var p=(0,n(4396).L8)(),v=function(e){return!!e&&"function"==typeof e&&"ajaxPrefilter"in e},y=function(e){return!!e&&"object"==typeof e&&"data"in e},h=function(e){return!!e&&"object"==typeof e&&"action"in e};var b=function(){return b=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},b.apply(this,arguments)},E=function(e,t,n,r){return new(n||(n=Promise))((function(o,i){function a(e){try{s(r.next(e))}catch(e){i(e)}}function l(e){try{s(r.throw(e))}catch(e){i(e)}}function s(e){var t;e.done?o(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(a,l)}s((r=r.apply(e,t||[])).next())}))},w=function(e,t){var n,r,o,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},a=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return a.next=l(0),a.throw=l(1),a.return=l(2),"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function l(l){return function(s){return function(l){if(n)throw new TypeError("Generator is already executing.");for(;a&&(a=0,l[0]&&(i=0)),i;)try{if(n=1,r&&(o=2&l[0]?r.return:l[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,l[1])).done)return o;switch(r=0,o&&(l=[2&l[0],o.value]),l[0]){case 0:case 1:o=l;break;case 4:return i.label++,{value:l[1],done:!1};case 5:i.label++,r=l[1],l=[0];continue;case 7:l=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==l[0]&&2!==l[0])){i=0;continue}if(3===l[0]&&(!o||l[1]>o[0]&&l[1]<o[3])){i.label=l[1];break}if(6===l[0]&&i.label<o[1]){i.label=o[1],o=l;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(l);break}o[2]&&i.ops.pop(),i.trys.pop();continue}l=t.call(e,i)}catch(e){l=[6,e],r=0}finally{n=o=0}if(5&l[0])throw l[1];return{value:l[0]?l[1]:void 0,done:!0}}([l,s])}}};function P(t){return E(this,void 0,Promise,(function(){var n;return w(this,(function(r){switch(r.label){case 0:return[4,S(t.postId)];case 1:return n=r.sent(),[4,A(b(b({},n),{date:"none"!==n.date&&n.date,followers:t.attributes.followers,statistics:{engagement:{},pageviews:void 0}}))];case 2:return r.sent(),void 0===(l=t.settings.isClassicEditor)&&(l=!1),(0,(0,e.dispatch)(i.store).setEditorToClassic)(l),window.NelioContentTinyMCE={createMessage:x,getLinks:a.getLinks,isEmpty:a.isEmpty},o=t.settings.autoShareEndModes,(0,a.isEmpty)(o)||(0,(0,e.dispatch)(i.store).setAutoShareEndModes)(o),g=t.settings.shouldAuthorBeFollower,(0,(0,e.dispatch)(i.store).includeAuthorInFollowers)(g),function(t){var n=t.url,r=t.alt;n&&(0,(0,e.dispatch)(i.store).setExternalFeaturedImage)(n,r)}(t.attributes.externalFeatImage),function(t){var n=(0,e.dispatch)(i.store),r=n.receiveReferences,o=n.suggestReferences;r(t);var l=(0,c.filter)(t,{isSuggestion:!0});(0,a.isEmpty)(l)||o((0,c.map)(l,"url"))}(t.attributes.references),function(t){(0,(0,e.dispatch)(i.store).markQualityAnalysisAsFullyIntegrated)(!!t.isFullyIntegrated)}(f=t.settings.qualityAnalysis),function(t){if(!t.isYoastIntegrated){var n=(0,e.dispatch)(i.store).removeQualityCheckType;n("nelio-content/yoast-content"),n("nelio-content/yoast-seo")}}(f),function(t){var n=t.supportsFeatImage,r=t.canImageBeAutoSet;n?(0,(0,e.dispatch)(i.store).updateQualityCheckSettings)("nelio-content/featured-image",{canImageBeAutoSet:r}):(0,(0,e.dispatch)(i.store).removeQualityCheckType)("nelio-content/featured-image")}(f),(0,(0,e.select)(d.store).isMultiAuthor)()||(0,(0,e.dispatch)(i.store).removeQualityCheckType)("nelio-content/author"),function(){var t,n,r,o=(0,e.dispatch)(i.store);n=(t=o).setCustomField,r=t.removeCustomField,m((function(){var e=window.jQuery;v(e)&&e.ajaxPrefilter((function(e,t){if(y(e)&&"string"==typeof e.data&&y(t)&&h(t)){var o=new URLSearchParams(e.data);switch(t.action){case"add-meta":if(o.has("metavalue")){var i="#NONE#"===o.get("metakeyselect")?o.get("metakeyinput"):o.get("metakeyselect"),a=o.get("metavalue")||"";i&&n(p(i),a)}else{var l=Array.from(o.keys()).filter((function(e){return/meta\[\d+\]\[key\]/.test(e)})).map((function(e){return o.get(e)}))[0],s=Array.from(o.keys()).filter((function(e){return/meta\[\d+\]\[value\]/.test(e)})).map((function(e){return o.get(e)}))[0];l&&s&&n(p(l),s)}break;case"delete-meta":var u=o.get("id"),c=u?document.getElementById("meta-".concat(u,"-key")):null;(i=null==c?void 0:c.getAttribute("value"))&&r(p(i))}}}))}))}(),(0,e.subscribe)((function(){var t=(0,e.select)(i.store),n=t.getDate,r=t.getStatus,o=n(),a=r();o===s&&a===u||(s=o,u=a,(0,(0,e.dispatch)(i.store).updateDatesInPostRelatedItems)())})),[2]}var o,l,s,u,f,g}))}))}function x(t,n){if(t=(0,c.trim)(t),!(0,a.isEmpty)(t)){var r=function(t){if((0,a.isEmpty)(t))return!1;var n=(0,e.select)(d.store).getSocialProfilesByNetwork;if((0,a.isEmpty)(n("twitter")))return!1;for(var r=(0,e.select)(i.store).getReferenceByUrl,o=0,l=t;o<l.length;o++){var s=r(l[o]);if(s&&s.twitter)return s.twitter}return!1}(n);r&&(t="".concat(t," /cc ").concat(r)),t+=" {permalink}";var o=(0,e.select)(i.store).getPost;(0,(0,e.dispatch)(f.store).openNewSocialMessageEditor)({text:t},{context:"post",post:o(),disabledProfileIds:(0,i.getDisabledProfiles)()})}}function S(e){return E(this,void 0,Promise,(function(){return w(this,(function(t){switch(t.label){case 0:return[4,u({path:"/nelio-content/v1/post/".concat(e,"?aws")})];case 1:return[2,t.sent()]}}))}))}function A(t){return E(this,void 0,void 0,(function(){var n,r;return w(this,(function(o){switch(o.label){case 0:return n=(0,e.dispatch)(i.store),r=n.loadPostItems,[4,(0,n.setPost)(t)];case 1:return o.sent(),r(),[2]}}))}))}const T=window.wp.plugins,k=window.NelioContent.taskEditor,I=window.NelioContent.components,C=window.wp.editPost;var O=function(t){var n=t.isQualityFullyIntegrated,r=(0,e.useDispatch)(C.store).openGeneralSidebar,o=(0,e.useDispatch)(i.store).togglePanel;return l.createElement(l.Fragment,null,!!n&&l.createElement(C.PluginPostStatusInfo,null,l.createElement(i.QualityAnalysisSummary,{onClick:function(){o("post-quality-analysis",!0),r("nelio-content/nelio-content-default-sidebar")}})),l.createElement(C.PluginPostStatusInfo,null,l.createElement(i.SocialMediaSummary,null)))};const _=window.wp.components,F=window.wp.i18n;var N=function(e,t,n){if(n||2===arguments.length)for(var r,o=0,i=t.length;o<i;o++)!r&&o in t||(r||(r=Array.prototype.slice.call(t,0,o)),r[o]=t[o]);return e.concat(r||Array.prototype.slice.call(t))},M=function(e){return t((function(t){var n=t(i.store).getPostType();return t(d.store).getPostTypes(e).some((function(e){return e.name===n}))}))},j=function(){var e=(0,i.usePanelToggling)("post-quality-analysis"),t=e[0],n=e[1];if(!M("quality-checks"))return null;var r=l.createElement(i.QualityAnalysisSummary,{label:(0,F._x)("Quality Analysis","text","nelio-content")});return l.createElement(_.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:r},l.createElement(i.QualityAnalysis,null))},R=function(){return l.createElement(C.PluginPrePublishPanel,null,l.createElement(j,null))},B=function(){var e=(0,i.usePanelToggling)("post-analytics"),n=e[0],r=e[1],o=t((function(e){return e(i.store).getPost()})),a="publish"===(null==o?void 0:o.status),s=M("analytics");return o&&s&&a?l.createElement(_.PanelBody,{initialOpen:n,opened:n,onToggle:r,title:(0,F._x)("Analytics","text","nelio-content")},l.createElement(I.PostPageviewsAnalytics,{postId:o.id}),l.createElement(I.PostEngagementAnalytics,{postId:o.id})):null},Q=function(){var e=(0,d.useIsSubscribed)(),t=(0,i.usePanelToggling)("comments"),n=t[0],r=t[1];return M("comments")?e?l.createElement(_.PanelBody,{initialOpen:n,opened:n,onToggle:r,title:(0,F._x)("Editorial Comments","text","nelio-content")},l.createElement(i.EditorialComments,null)):l.createElement(I.PremiumPlaceholderPanel,{title:(0,F._x)("Editorial Comments","text","nelio-content"),feature:"raw/editorial-comments"}):null},D=function(){var e=(0,d.useIsSubscribed)(),t=(0,i.usePanelToggling)("tasks"),n=t[0],r=t[1];return M("tasks")?e?l.createElement(_.PanelBody,{initialOpen:n,opened:n,onToggle:r,title:(0,F._x)("Editorial Tasks","text","nelio-content")},l.createElement(i.EditorialTasks,null)):l.createElement(I.PremiumPlaceholderPanel,{title:(0,F._x)("Editorial Tasks","text","nelio-content"),feature:"raw/editorial-tasks"}):null},q=function(){var e=(0,i.usePanelToggling)("external-featured-image"),t=e[0],n=e[1];return M("efi")?l.createElement(_.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,F._x)("External Featured Image","text","nelio-content")},l.createElement(i.ExternalFeaturedImage,null)):null},U=function(){var e=(0,i.usePanelToggling)("notifications"),t=e[0],n=e[1];return M("notifications")?l.createElement(_.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,F._x)("Notifications","text","nelio-content")},l.createElement(i.Notifications,null)):null},L=function(){var e=(0,i.usePanelToggling)("references"),t=e[0],n=e[1];return M("references")?l.createElement(_.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,F._x)("References","text","nelio-content")},l.createElement(i.References,null)):null},G=function(){var e=(0,i.usePanelToggling)("social-media"),t=e[0],n=e[1];return M("social")?l.createElement(_.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,F._x)("Social Media","text","nelio-content")},l.createElement(i.SocialMediaTools,null)):null},H=function(){var e=t((function(e){return e(d.store).getToday()}));return(0,a.hasSubscriptionPromo)(e)?l.createElement("div",{style:{padding:"1em",display:"flex"}},l.createElement("div",{style:{textAlign:"center",width:"100%"}},l.createElement(I.SubscribeAction,null))):null},z=function(){if(!t((function(e){var t=e(i.store).getPostType(),n=e(d.store).getPostTypes;return N(N(N(N(N(N(N([],n("comments"),!0),n("efi"),!0),n("future-actions"),!0),n("notifications"),!0),n("quality-checks"),!0),n("references"),!0),n("tasks"),!0).some((function(e){return e.name===t}))})))return null;var e=(0,I.getPremiumComponent)("gutenberg-editor/future-actions-panel",(function(){return l.createElement(I.PremiumPlaceholderPanel,{title:(0,F._x)("Future Actions","text","nelio-content"),feature:"raw/future-actions"})}));return l.createElement(C.PluginSidebar,{name:"nelio-content-content-tools-sidebar",title:(0,F._x)("Nelio Content Tools","text","nelio-content")},l.createElement(H,null),l.createElement(j,null),l.createElement(B,null),l.createElement(G,null),l.createElement(U,null),l.createElement(e,null),l.createElement(D,null),l.createElement(Q,null),l.createElement(L,null),l.createElement(q,null))},J=function(){return M("social")?l.createElement(C.PluginSidebar,{name:"nelio-content-social-sidebar",title:(0,F._x)("Nelio Content Social","text","nelio-content"),icon:"share"},l.createElement(i.SocialMediaSidebar,null)):null},Y=function(e){var t=e.isQualityFullyIntegrated,n=(0,I.getPremiumComponent)("post-page/future-action-editor","null");return l.createElement(l.Fragment,null,l.createElement(O,{isQualityFullyIntegrated:t}),l.createElement(R,null),l.createElement(z,null),l.createElement(J,null),l.createElement(i.ReferenceEditor,null),l.createElement(f.SocialMessageEditor,null),l.createElement(k.TaskEditor,null),l.createElement(n,null),l.createElement(I.PremiumDialog,null))};const K=window.React;var V;function W(){return W=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)({}).hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},W.apply(null,arguments)}const X=function(e){return K.createElement("svg",W({xmlns:"http://www.w3.org/2000/svg",width:20,height:20,viewBox:"0 0 40 40"},e),V||(V=K.createElement("path",{fill:"inherit",d:"M20 2.5C10.335 2.5 2.5 10.335 2.5 20S10.335 37.5 20 37.5c5.532 0 10.457-2.574 13.664-6.582l-2.97-2.285C28.172 31.75 24.321 33.75 20 33.75c-7.594 0-13.75-6.156-13.75-13.75S12.406 6.25 20 6.25c3.146 0 6.036 1.069 8.352 2.848l-8.63 8.629-4.867-4.868-2.285 2.286 4.868 4.867 2.285 2.285 2.285-2.285 8.668-8.668 2.66-2.66q-.531-.623-1.121-1.188a18 18 0 0 0-1.201-1.06C28.005 3.986 24.182 2.5 20 2.5m14.428 9.598a1.886 1.886 0 0 0-1.885 1.884 1.886 1.886 0 0 0 1.885 1.887 1.886 1.886 0 0 0 1.886-1.887 1.886 1.886 0 0 0-1.886-1.884m1.187 6.017A1.886 1.886 0 0 0 33.73 20a1.886 1.886 0 0 0 1.886 1.885A1.886 1.886 0 0 0 37.5 20a1.886 1.886 0 0 0-1.885-1.885m-1.209 6.102a1.886 1.886 0 0 0-1.885 1.885 1.886 1.886 0 0 0 1.885 1.884 1.886 1.886 0 0 0 1.887-1.884 1.886 1.886 0 0 0-1.887-1.885"})))};var Z=function(){return Z=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},Z.apply(this,arguments)};const $=window.NelioContent.date;const ee=window.wp.coreData;var te=function(){return te=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},te.apply(this,arguments)},ne=function(e){return"number"==typeof e},re=function(e){return!!e&&"object"==typeof e&&"id"in e&&"name"in e&&"slug"in e};function oe(){var t,n,r,l,s,u,f,g,m=(0,e.dispatch)(i.store);t=(0,e.select)(o.store).isEditedPostDirty,n=(0,e.select)(i.store).getPostId,r=(0,e.select)(d.store).getPost,l=(0,e.dispatch)(d.store),s=l.invalidateResolution,u=l.removePost,f=t(),g=n(),(0,e.subscribe)((function(){var e=t();if(e!==f){var n=f;f=e,g&&n&&!e&&(u(g),s("getPost",[g]),r(g))}})),function(t){var n,r=t.setPostId,i=(0,e.select)(o.store),a=i.getEditedPostAttribute,l=i.isEditedPostNew;(0,e.subscribe)((function(){if(!l()){var e=a("id");n!==e&&(n=e,r(e))}}))}(m),function(t){var n,r=t.setStatus,i=(0,e.select)(o.store).getEditedPostAttribute;(0,e.subscribe)((function(){var e=i("status");n!==e&&(n=e,r(e))}))}(m),function(t){var n,r,l=t.setFeaturedImage,s=(0,e.select)(i.store).getExternalFeaturedImageUrl,u=(0,e.select)(o.store).getEditedPostAttribute,c=(0,e.select)(ee.store).getEntityRecord;(0,e.subscribe)((function(){var e=s();if(e){if(0===n&&r===e)return;return r=e||!1,void l(n=0,e)}var t=u("featured_media"),o=t?c("root","media",t):void 0,i=null==o?void 0:o.source_url,d=!!(0,a.isUrl)(i)&&i;n===t&&r===d||(n=t,r=d,l(t,d))}))}(m),function(t){var n,r=t.setTitle,i=(0,e.select)(o.store).getEditedPostAttribute,a=(0,c.debounce)(r,500);(0,e.subscribe)((function(){var e=i("title");n!==e&&(n=e,a(e))}))}(m),function(t){var n,r=t.setDate,i=(0,e.select)(o.store),a=i.isEditedPostDateFloating,l=i.getEditedPostAttribute;(0,e.subscribe)((function(){var e,t=function(e){if(e){var t=e.substring(0,10),n=e.substring(11,16);return(0,$.wpifyDateTime)("c",t,n)}}(a()?"":null!==(e=l("date"))&&void 0!==e?e:"");n!==t&&(n=t,r(t))}))}(m),function(t){var n,r=t.setContent,i=(0,e.select)(o.store).getEditedPostAttribute,a=(0,c.debounce)(r,2500);(0,e.subscribe)((function(){var e=i("content");n!==e&&(n=e,a(e))}))}(m),function(t){var n,r=t.setExcerpt,i=(0,e.select)(o.store).getEditedPostAttribute,a=(0,c.debounce)(r,500);(0,e.subscribe)((function(){var e=i("excerpt");n!==e&&(n=e,a(e))}))}(m),function(t){var n,r=t.setPermalink,i=(0,e.select)(o.store).getPermalink,l=(0,c.debounce)(r,500);(0,e.subscribe)((function(){var e,t=null!==(e=i())&&void 0!==e?e:"";n!==t&&(n=t,(0,a.isUrl)(t)&&l(t))}))}(m),function(t){var n,r,i=t.setAuthor,a=(0,e.select)(o.store).getEditedPostAttribute,l=(0,e.select)(d.store).getAuthor;(0,e.subscribe)((function(){var e,t=a("author"),o=l(t),s=null!==(e=null==o?void 0:o.name)&&void 0!==e?e:"";n===t&&r===s||(n=t,r=s,i(t,s))}))}(m),function(t){var n=t.setTerms,r=(0,e.select)(i.store).getPostType,a=(0,e.select)(o.store).getEditedPostAttribute,l=void 0===a?function(){return[]}:a,s=(0,e.select)(ee.store),u=s.getEntityRecord,d=s.getEntityRecords,f="";(0,e.subscribe)((0,c.debounce)((function(){var e=d("root","taxonomy",{per_page:-1})||[],t=(0,c.filter)(e,(function(e){var t;return e.visibility.public&&e.types.includes(null!==(t=r())&&void 0!==t?t:"")})).map((function(e){return e.slug})),o=t.map((function(e){var t=function(e){switch(e){case"category":return"categories";case"post_tag":return"tags";default:return e}}(e),n=l(t);return(0,c.castArray)(n).filter(ne).map((function(t){return u("taxonomy",e,t)})).filter(re).map((function(e){return{id:e.id,name:e.name,slug:e.slug}}))})),i=JSON.stringify(o);i!==f&&(f=i,t.forEach((function(e,t){var r=o[t];r&&n(e,r)})))}),500))}(m),function(t){var n=t.setPost,r=(0,e.select)(i.store).getPost,a=(0,e.select)(o.store),l=a.isSavingPost,s=a.isAutosavingPost,u=a.isPreviewingPost,c=l(),d=s(),f=u();(0,e.subscribe)((function(){var e=l(),t=s(),o=u(),i=c&&!e&&!d||d&&f&&!o;if(c=e,d=t,f=o,i){var a=r();S(a.id).then((function(e){return n(te(te({},a),{customFields:e.customFields,customPlaceholders:e.customPlaceholders}))}))}}))}(m)}const ie=window.wp.blockEditor,ae=window.wp.richText;var le="nelio-content/highlight",se="nelio-content/share";function ue(e){return!(0,a.isEmpty)(ce(e))}function ce(e){var t,n=(0,c.trim)(e.text.substring(null!==(t=e.start)&&void 0!==t?t:0,e.end));return(0,c.capitalize)(n[0])+n.substring(1)}function de(e){var t,n,r=(0,c.find)(e,{type:"core/link"});return null!==(n=null===(t=null==r?void 0:r.attributes)||void 0===t?void 0:t.url)&&void 0!==n?n:""}var fe=function(e,t,n,r){return new(n||(n=Promise))((function(o,i){function a(e){try{s(r.next(e))}catch(e){i(e)}}function l(e){try{s(r.throw(e))}catch(e){i(e)}}function s(e){var t;e.done?o(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(a,l)}s((r=r.apply(e,t||[])).next())}))},ge=function(e,t){var n,r,o,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},a=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return a.next=l(0),a.throw=l(1),a.return=l(2),"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function l(l){return function(s){return function(l){if(n)throw new TypeError("Generator is already executing.");for(;a&&(a=0,l[0]&&(i=0)),i;)try{if(n=1,r&&(o=2&l[0]?r.return:l[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,l[1])).done)return o;switch(r=0,o&&(l=[2&l[0],o.value]),l[0]){case 0:case 1:o=l;break;case 4:return i.label++,{value:l[1],done:!1};case 5:i.label++,r=l[1],l=[0];continue;case 7:l=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==l[0]&&2!==l[0])){i=0;continue}if(3===l[0]&&(!o||l[1]>o[0]&&l[1]<o[3])){i.label=l[1];break}if(6===l[0]&&i.label<o[1]){i.label=o[1],o=l;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(l);break}o[2]&&i.ops.pop(),i.trys.pop();continue}l=t.call(e,i)}catch(e){l=[6,e],r=0}finally{n=o=0}if(5&l[0])throw l[1];return{value:l[0]?l[1]:void 0,done:!0}}([l,s])}}};function me(t){return fe(this,void 0,Promise,(function(){return ge(this,(function(n){switch(n.label){case 0:return s=function(t){var n=(0,(0,e.select)(o.store).getEditedPostAttribute)("nelio_content");n&&((0,a.isEquivalent)(t,n)||(0,(0,e.select)(o.store).getCurrentPost)().id&&(0,(0,e.dispatch)(o.store).editPost)({nelio_content:t},{undoIgnore:!0}))},u={},(0,e.subscribe)((function(){var t=(0,e.select)(i.store),n=t.getAutoShareEndMode,r=t.getAutomationSources,o=t.getExternalFeaturedImageAlt,a=t.getExternalFeaturedImageUrl,l=t.getPost,d=t.getQueryArgs,f=t.getSuggestedReferences,g=t.isAutoShareEnabled,m=l(),p=(0,i.processHtml)(m.content).highlights,v={autoShareEndMode:n(),automationSources:r(),efiAlt:o(),efiUrl:a()||"",followers:m.followers,highlights:p,isAutoShareEnabled:g(),permalinkQueryArgs:d(),suggestedReferences:f()};(0,c.isEqual)(v,u)||s(v),u=v})),[4,P(t)];case 1:return n.sent(),r={isQualityFullyIntegrated:t.settings.qualityAnalysis.isFullyIntegrated},(0,T.registerPlugin)("nelio-content",{icon:l.createElement(X,null),render:function(){return l.createElement(Y,Z({},r))}}),function(e,t){var n=document.getElementById(e);if(n){var r,o,i=n.querySelector(".inside");i&&(r=l.createElement(t,null),(o=i)&&(l.createRoot?(0,l.createRoot)(o).render(r):(0,l.render)(r,o)))}}("nelio-content-social-media",i.SocialMediaMetabox),oe(),[2]}var r,s,u}))}))}(0,ae.registerFormatType)(le,{name:le,title:(0,F._x)("Social Media Highlight","text","nelio-content"),tagName:"ncshare",className:null,edit:function(e){var t=e.isActive,n=e.value,r=e.onChange;return l.createElement(ie.RichTextToolbarButton,{icon:l.createElement(X,null),title:(0,F._x)("Social Media Highlight","text","nelio-content"),onClick:function(){return r((0,ae.toggleFormat)(n,{type:le}))},isActive:t})},interactive:!1}),(0,ae.registerFormatType)(se,{name:se,title:(0,F._x)("Share","command","nelio-content"),tagName:"span",className:"nc-share",edit:function(e){var n=e.value,r=t((function(e){return e(d.store).getSocialProfileIds()})),o=(0,i.useDisabledProfiles)().length<r.length;return l.createElement(ie.RichTextToolbarButton,{icon:"share",title:(0,F._x)("Share","command","nelio-content"),isDisabled:!ue(n)||!o,onClick:function(){return x(ce(n),function(e){var t,n=null!==(t=(0,c.slice)(e.formats,e.start,e.end))&&void 0!==t?t:[];return(0,c.uniq)((0,c.filter)(n.map(de))).filter(a.isUrl)}(n))}})},interactive:!1})})();var o=NelioContent="undefined"==typeof NelioContent?{}:NelioContent;for(var i in r)o[i]=r[i];r.__esModule&&Object.defineProperty(o,"__esModule",{value:!0})})();