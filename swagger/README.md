# Swagger API Documentation

This directory contains the Swagger/OpenAPI specification for the Gun Crime Reporting API. The specification is defined in the `swagger.yaml` file, which outlines the API endpoints, request/response formats, and other relevant details.

## Getting Started

To generate and view the API documentation using Swagger UI, follow these steps:

1. **Install Swagger UI**: You can either download Swagger UI from the [Swagger UI GitHub repository](https://github.com/swagger-api/swagger-ui) or use a hosted version.

2. **Load the Swagger Specification**:
   - If you are using a local installation of Swagger UI, place the `swagger.yaml` file in the `dist` directory of Swagger UI.
   - Open `index.html` in your browser and update the `url` parameter to point to your `swagger.yaml` file.

3. **View the Documentation**: Once the Swagger UI is set up, you can view the API documentation in your browser. The UI will display all available endpoints, their parameters, and response formats.

## API Specification

The `swagger.yaml` file contains the following sections:

- **Info**: General information about the API, including title, version, and description.
- **Paths**: Detailed information about each API endpoint, including HTTP methods, parameters, and responses.
- **Components**: Definitions of reusable components such as schemas for request and response bodies.

## Contributing

If you wish to contribute to the API documentation:

- Ensure that the `swagger.yaml` file is updated with any new endpoints or changes to existing endpoints.
- Follow the OpenAPI Specification guidelines for formatting and structuring the documentation.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.