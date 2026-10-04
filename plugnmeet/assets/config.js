// PlugNmeet Client — NPC White-Labeled Config
// Points to local Docker PlugNMeet server for signaling with NPC branding overrides.
const plugNmeetBasePath = window.location.pathname.startsWith('/plugnmeet') ? '/plugnmeet' : '';
const wsProto = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
const wsHost = window.location.host;

// Dynamic NATS WebSocket route: If running on direct Docker 8085, use :8222;
// otherwise use the host /nats proxy (works seamlessly over Cloudflare WSS & local)
let natsUrls;
if (window.location.port === '8085') {
  natsUrls = ['http://localhost:8222'];
} else {
  natsUrls = [wsProto + wsHost + '/nats'];
}

window.plugNmeetConfig = {
  serverUrl: window.location.origin + plugNmeetBasePath,
  staticAssetsPath: plugNmeetBasePath ? (plugNmeetBasePath + '/assets') : '/assets',
  natsWSUrls: natsUrls,

  // No logo as requested by user
  customLogo: {
    main_logo_light: '',
    main_logo_dark: ''
  },

  enableAdaptiveStream: true,
  enableDynacast: true,
  enableSimulcast: true,
  videoCodec: 'vp8',
  defaultWebcamResolution: 'h720',
  defaultScreenShareResolution: 'h1080fps15',
  defaultAudioPreset: 'music',
  stopMicTrackOnMute: true,
  focusActiveSpeakerWebcam: true,
  disableDarkMode: false,

  // Clean aesthetics with zero logo and zero timer
  designCustomization: {
    primary_color: '#059669',
    secondary_color: '#0284c7',
    header_bg_color: '#0f172a',
    footer_bg_color: '#0f172a',
    footer_icon_bg_color: '#059669',
    footer_icon_color: '#ffffff',
    background_color: '#020617',
    custom_logo: '',
    custom_css_url: plugNmeetBasePath + '/assets/npc-overrides.css'
  }
};
