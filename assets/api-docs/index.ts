import { createApiReference } from '@scalar/api-reference';
import '@scalar/api-reference/style.css';

createApiReference('#scalar-api-reference', {
  baseServerURL: window.location.origin,
  sources: [
    {
      title: 'Publication API v1',
      slug: 'publication-v1',
      url: '/api/publication/v1/openapi.json',
      default: true,
    },
    {
      title: 'Admin API',
      slug: 'admin',
      url: '/api/admin/openapi.json',
    },
  ],
  hideClientButton: true,
  telemetry: false,
  hideTestRequestButton: true,
  expandAllResponses: true,
});
